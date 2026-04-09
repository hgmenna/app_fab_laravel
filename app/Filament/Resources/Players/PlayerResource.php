<?php

namespace App\Filament\Resources\Players;

use App\Filament\Resources\Players\Pages\CreatePlayer;
use App\Filament\Resources\Players\Pages\EditPlayer;
use App\Filament\Resources\Players\Pages\ListPlayers;
use App\Filament\Resources\Players\Schemas\PlayerForm;
use App\Filament\Resources\Players\Tables\PlayersTable;
use App\Helpers\FabPath;
use App\Imports\PlayersImport;
use App\Models\Category;
use App\Models\GeneralRanking;
use App\Models\Membership;
use App\Models\Player;
use App\Services\AdminNotifier;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Excel;
use UnitEnum;

class PlayerResource extends Resource
{
    protected static ?string $model = Player::class;
    protected static string|UnitEnum|null $navigationGroup = 'Gestión Deportiva';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::Users;
    protected static ?string $recordTitleAttribute = 'name';
    protected static ?string $navigationLabel = 'Jugadores';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return PlayerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PlayersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPlayers::route('/'),
            'create' => CreatePlayer::route('/create'),
            'edit' => EditPlayer::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    // Generacion de archivo Pdf
    public static function exportToPdf($records, string $title)
    {
        // Aumentar recursos para reportes pesados [2]
        ini_set('memory_limit', '512M');
        set_time_limit(300);

        $totalPlayers = $records->count();  // Total General

        // 1. DEFINICIÓN DE COLUMNAS (Sin columna CLUB porque se agrupa) [3]
        // Ancho total aproximado: 680px para Landscape
        $columns = [
            ['label' => 'APELLIDO',  'field' => 'last_name',        'width' => 180],
            ['label' => 'NOMBRE',    'field' => 'first_name',       'width' => 180],
            ['label' => 'PROVINCIA', 'field' => 'provincia_display','width' => 200],
            ['label' => 'CAT',       'field' => 'ranking_category', 'width' => 120],
        ];

        // 2. ORDENAMIENTO ALFABÉTICO (Directo sobre el modelo Player)
        $sortedRecords = $records->sortBy([
            ['last_name', 'asc'],
            ['first_name', 'asc'],
        ]);

        // 3. PROCESAMIENTO Y MAPEADO DE DATOS [1]
        $processed = $sortedRecords->map(function ($row) {
            // Búsqueda de Ranking usando los datos directos del Player [4, 5]
            $ranking = GeneralRanking::where('first_name', $row->first_name)
                ->where('last_name', $row->last_name)
                ->first();

            $categoryCode = $ranking?->category;
            $categoryName = Category::where('code', $categoryCode)->value('name');

            $category_display = $categoryName ?? ($row->category?->name ?? '-');

            return (object)[
                'last_name'        => $row->last_name,
                'first_name'       => $row->first_name,
                'provincia_display'=> $row->club?->city?->state?->name ?? 'N/A',
                'ranking_category' => $category_display,
                'club_group'       => $row->club->name ?? 'SIN INSTITUCIÓN' // Para agrupar
            ];
        });

        // 4. AGRUPACIÓN POR CLUB Y ORDEN ALFABÉTICO DE GRUPOS
        $grouped = $processed->groupBy('club_group')->sortKeys();

        // 5. CARGA DE VISTA Y CONFIGURACIÓN [3, 6]
        $pdf = Pdf::loadView('pdf.generic', [
            'title'        => $title,
            'subtitle'     => '',
            'date'         => now()->format('d/m/Y'),
            'columns'      => $columns,
            'groups'       => $grouped, // Enviamos como grupos para repetir el TH [3]
            'totalGeneral' => $totalPlayers,
            'labelTotalGeneral' => 'Afiliados',
            'logo'         => FabPath::logo(),
            'footer_image' => FabPath::footer(),
        ])->setPaper('a4', 'portrait'); // Orientación horizontal [6]

        // 6. DESCARGA MEDIANTE STREAM [6]
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, $title . ' - ' . now()->format('Y-m-d') . '.pdf');
    }

    // Accion para almacenar Pago Afilicacion del año corriente
    public static function payMembershipAction(): Action
    {
        $userAuth = Auth::user();

        return Action::make('payMembership')
            ->label('Afiliación')
            ->icon('heroicon-o-credit-card')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Confirmar Pago de Afiliacion')
            ->visible(fn () => $userAuth?->can('PayMembership') ?? false)
            ->disabled(fn ($record) => $record?->is_enabled_to_compete)
            ->action(fn ($records) => static::processPayMembership($records));
    }

    public static function processPayMembership($records): void
    {
        // Normalizar: si es un solo registro, convertirlo en array
        $records = is_iterable($records) ? $records : [$records];

        foreach ($records as $record) {
            try {
                // 1) Buscar membresía activa del año actual
                $activeMembership = Membership::where('active', true)
                    ->where('year', now()->year)
                    ->first();

                if (!$activeMembership) {
                    // Crear membresía activa automáticamente
                    $activeMembership = Membership::create([
                        'year' => now()->year,
                        'discipline_id' => $record->discipline_id ?? 1, // Ajustar según tu lógica
                        'amount' => 0, // O el monto institucional que corresponda
                        'active' => true,
                    ]);
                }

                // 2) Buscar membresía del jugador para el año actual
                $playerMembership = $record->memberships()
                    ->where('membership_id', $activeMembership->id)
                    ->first();

                // 3) Si ya existe y está aprobada → habilitar y continuar
                if ($playerMembership && $playerMembership->status === 'approved') {
                    $record->update(['is_enabled_to_compete' => true]);
                    continue;
                }

                // 4) Si no existe → crearla
                if (!$playerMembership) {
                    $playerMembership = $record->memberships()->create([
                        'membership_id' => $activeMembership->id,
                        'club_id' => $record->club_id,
                        'amount_due' => $activeMembership->amount,
                        'amount_paid' => 0,
                        'status' => 'pending',
                    ]);
                }

                // 5) Registrar pago
                $payment = $playerMembership->payments()->create([
                    'payer_type' => 'player',
                    'payer_id' => $record->id,
                    'amount' => $activeMembership->amount,
                    'method' => 'manual',
                    'status' => 'pending',
                    'external_reference' => 'MANUAL-' . uniqid(),
                ]);

                // 6) Aprobar pago
                $payment->approve();

                // 7) Habilitar jugador
                $record->update(['is_enabled_to_compete' => true]);

                // 8) Notificación institucional
                AdminNotifier::send(
                    pageInstance: null,
                    record: $record,
                    operation: 'habilitó para competir (Pago Membresía)',
                    displayFields: ['last_name', 'first_name'],
                    customResourceName: 'jugador'
                );

            } catch (\Throwable $e) {
                AdminNotifier::sendException($e);

                Notification::make()
                    ->title('Error en el proceso')
                    ->danger()
                    ->send();
            }
        }

        Notification::make()
            ->title('Proceso completado')
            ->success()
            ->send();
    }

    // Accion Exportar registros filtrados en archivo Pdf
    public static function exportarPdf(): Action
    {
        return Action::make('descargarPdf')
            ->label('Exportar PDF')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('success')
            ->action(function ($livewire) {
                // Obtenemos los registros filtrados desde el componente Livewire
                $records = $livewire->getFilteredTableQuery()->get();
                
                // Invocamos el método estático del Resource
                return PlayerResource::exportToPdf($records, 'Listado de Jugadores');
            }
        );
    }

    // Accion para importar Jugadores
    public static function importPlayers(): Action
    {
        $userAuth = Auth::user();
        return
            Action::make('importPlayers')
                ->label('Importar jugadores')
                ->visible(fn () => $userAuth?->name === 'super-admin')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->schema([
                    FileUpload::make('file')
                        ->label('Archivo Excel')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ])
                        ->required(),
                ])
                ->action(function (array $data) {
                    Excel::import(new PlayersImport, $data['file']);
                })
                ->modalHeading('Importar jugadores desde Excel')
                ->modalSubmitActionLabel('Importar');
    }


}
