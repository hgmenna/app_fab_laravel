<?php

namespace App\Filament\Resources\TournamentRegistrations;

use App\Filament\Resources\TournamentRegistrations\Pages\CreateTournamentRegistration;
use App\Filament\Resources\TournamentRegistrations\Pages\EditTournamentRegistration;
use App\Filament\Resources\TournamentRegistrations\Schemas\TournamentRegistrationForm;
use App\Filament\Resources\TournamentRegistrations\Tables\TournamentRegistrationsTable;
use App\Models\GeneralRanking;
use App\Models\TournamentInstance;
use App\Models\TournamentRegistration;
use App\Services\RankingService;
use App\Services\TournamentRegistrationPdfService;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use UnitEnum;

class TournamentRegistrationResource extends Resource
{

    protected static ?string $model = TournamentRegistration::class;
    protected static string|UnitEnum|null $navigationGroup = 'Torneos';
    protected static ?string $navigationLabel = 'Inscripciones';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $title = 'Inscripciones';
    protected static ?string $recordTitleAttribute = 'name';


    public static function form(Schema $schema): Schema
    {
        return TournamentRegistrationForm::configure($schema, request()->route('record'));
    }

    public static function table(Table $table): Table
    {
        return TournamentRegistrationsTable::configure($table, request()->route('record'));
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
            'index' => Pages\ListTournamentRegistrations::route('/'),
            'create' => CreateTournamentRegistration::route('/create'),
            'edit' => EditTournamentRegistration::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function exportRegistrationsToPdf($tournament, $slotId = null)
    {
        ini_set('memory_limit', '512M');
        set_time_limit(300);

        // 1. Obtener inscripciones usando la relación correcta 'slot' definida en el modelo [3]
        // Se usa $tournament->registrations() asumiendo que el modelo Tournament tiene esta relación [1]
        $query = $tournament->registrations()
            ->with(['player.club', 'slot']);

        if ($slotId) {
            $query->where('tournament_slot_id', $slotId);
        }

        $registrations = $query->get();
        $totalGeneral = $registrations->count(); // Total de inscriptos
        $tournamentType = $tournament->type->short_name;

        if($tournamentType !== 'CAB' && $tournament->venue !== null) {
            $logo = $tournament->venue->logo_path;
        }

        // 2. Procesar datos y Ranking (basado en fuentes [4-6])
        $processed = $registrations->map(function ($reg) {
            $player = $reg->player;
            
            // Búsqueda en el Ranking General (Fuente [4, 5])
            $generalRanking = GeneralRanking::where('first_name', $player?->first_name)
                ->where('last_name', $player?->last_name)
                ->first();

            return (object)[
                'last_name'        => $player->last_name,
                'first_name'       => $player->first_name,
                'club_display'     => $player->club->name ?? 'N/A',
                'provincia_display' => $player->club?->city?->state?->name ?? 'N/A',
                // Lógica solicitada: Si no hay ranking, usa la categoría del jugador [4]
                'ranking_category' => $generalRanking?->category ?? $player->category?->code ?? '-',
                'ranking_rg'       => Str::limit($generalRanking?->RG ?? 9999, 4), // 9999 para ordenar al final
                'slot_name'        => $reg->slot->name ?? 'Sin Horario' // Usando relación 'slot' [3]
            ];
        });

        // 3. Ordenar por RG Ascendente y Agrupar por Horario (Slot)
        $grouped = $processed->sortBy('ranking_rg')->groupBy('slot_name');

        $columns = [
            ['label' => 'APELLIDO', 'field' => 'last_name', 'width' => 120],
            ['label' => 'NOMBRE', 'field' => 'first_name', 'width' => 120],
            ['label' => 'CLUB', 'field' => 'club_display', 'width' => 220],
            ['label' => 'PROVINCIA', 'field' => 'provincia_display', 'width' => 150],
            ['label' => 'CAT', 'field' => 'ranking_category', 'width' => 35],
            ['label' => 'RG', 'field' => 'ranking_rg', 'width' => 35],
        ];

        // 4. Generar PDF con la vista genérica (asegúrate de que use el encabezado de tabla solicitado)
        $pdf = Pdf::loadView('pdf.generic', [
            'title'    => $tournament->name,
            'subtitle' => $slotId ? 'Nomina de Jugadores por Horario': 'Nomina de Jugadores Inscriptos',
            'date'     => now()->format('d/m/Y'),
            'columns'  => $columns,
            'groups'   => $grouped,
            'totalGeneral' => $totalGeneral, // Pasamos el total de inscriptos
            'labelTotalGeneral' => 'Inscriptos',
            'logo'     => public_path('images/logo.png'),
            'footer_image' => public_path('images/pie-pagina.png'),
        ])->setPaper('a4', 'portrait');

        // Nombre dinamico para el pdf
        $fileName = $slotId
            ? "Inscripciones-{$tournament->name}-{$slotId}.pdf"
            : "Inscripciones-{$tournament->name}.pdf";

        return response()->streamDownload(fn () => print($pdf->output()), $fileName);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false; // antes estaba false por ser nested
    }

    public static function isNested($livewire): bool
    {
        return $livewire instanceof \Filament\Resources\RelationManagers\RelationManager || 
            $livewire instanceof \Filament\Resources\Pages\ManageRelatedRecords;
    }

   public static function AsignInstanceAction(): Action
   {
        return
            Action::make('asignarInstancia')
                    ->label('Asignar Posicion')
                    ->visible(fn (?TournamentRegistration $record) => 
                        Auth::user()?->can('EditField') && 
                        $record?->tournament?->start_date < now()
                    )
                    ->disabled(fn (TournamentRegistration $record) => 
                        ($record->tournament?->start_date > now()) &&
                        (Auth::user()->name !== 'super-admin')
                    )
                    ->modalHeading('Asignar Posicion y calcular puntos')
                    ->form([
                        Select::make('tournament_instance_id')
                            ->label('Instancia')
                            ->options(
                                TournamentInstance::pluck('description', 'id')->toArray()
                            )
                            ->nullable(), // permitir null

                        TextInput::make('penalty_points')
                            ->label('Penalizacion')
                            ->numeric()
                            ->default(0)
                            ->helperText('Puntos a descontar por inasistencia en Master/1ra.'),
                    ])
                    ->action(function (array $data, TournamentRegistration $record) {

                        // Guardar instancia (puede ser null)
                        $record->tournament_instance_id = $data['tournament_instance_id'] ?? null;
                        $record->penalty_points = $data['penalty_points'] ?? 0; 
                        $record->save();

                        // Recargar relaciones para evitar usar la instancia vieja en memoria
                        $record->refresh();

                        // Si no hay instancia, puntos = null
                        if (! $record->tournament_instance_id) {
                            $record->points = null;
                        } else {
                            $record->points = $record->calculatePoints();

                        }

                        $record->save();
                        RankingService::syncGeneralRanking();
                    }
                )
                ->color('primary');
   }

    public static function afterSave($record): void
    {
        $url = TournamentRegistrationPdfService::generate($record);
        session()->flash('pdf_url', $url);
    }





}
