<?php

namespace App\Filament\Resources\Clubs;

use App\Filament\Resources\Clubs\Pages\CreateClub;
use App\Filament\Resources\Clubs\Pages\EditClub;
use App\Filament\Resources\Clubs\Pages\ListClubs;
use App\Filament\Resources\Clubs\Schemas\ClubForm;
use App\Filament\Resources\Clubs\Tables\ClubsTable;
use App\Models\Club;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;

class ClubResource extends Resource
{
    protected static ?string $model = Club::class;
    protected static string|UnitEnum|null $navigationGroup = 'Gestión Deportiva';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::BuildingOffice2;
    protected static ?string $navigationLabel = 'Clubes';
    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return ClubForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ClubsTable::configure($table);
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
            'index' => ListClubs::route('/'),
            'create' => CreateClub::route('/create'),
            'edit' => EditClub::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function exportToPdf($records, string $title)
    {
        // Aumentar recursos para reportes pesados [2]
        ini_set('memory_limit', '512M');
        set_time_limit(300);

        $totalClubes = $records->count(); // Total General

        // 1. DEFINICIÓN DE COLUMNAS (Sin columna CLUB porque se agrupa) [3]
        // Ancho total aproximado: 680px para Landscape
        $columns = [
            ['label' => 'NOMBRE',  'field' => 'name',        'width' => 180],
            ['label' => 'DIRECCION',    'field' => 'address',       'width' => 180],
            ['label' => 'CIUDAD', 'field' => 'city_name','width' => 180],
            ['label' => 'AFILIADOS',       'field' => 'cant_afil', 'width' => 80],
        ];

        // 2. ORDENAMIENTO ALFABÉTICO (Directo sobre el modelo Player)
        $sortedRecords = $records->sortBy([
            ['name', 'asc'],
        ]);

        // 3. PROCESAMIENTO Y MAPEADO DE DATOS [1]
        $processed = $sortedRecords->map(function ($row) {

            return (object)[
                'name'        => $row->name,
                'address'       => $row->address,
                'city_name'=> $row->city?->name ?? 'N/A',
                'cant_afil'       => $row->players?->count() ?? 0, // Para agrupar
                'federation_group' => $row->city?->state?->federation?->name ?? '-',
            ];
        });

        // 4. AGRUPACIÓN POR CLUB Y ORDEN ALFABÉTICO DE GRUPOS
        $grouped = $processed->groupBy('federation_group')->sortKeys();

        // 5. CARGA DE VISTA Y CONFIGURACIÓN [3, 6]
        $pdf = Pdf::loadView('pdf.generic', [
            'title'        => $title,
            'subtitle'     => '',
            'date'         => now()->format('d/m/Y'),
            'columns'      => $columns,
            'groups'       => $grouped, // Enviamos como grupos para repetir el TH [3]
            'totalGeneral' => $totalClubes,
            'labelTotalGeneral' => 'Clubes',
            'logo'         => public_path('images/logo.png'),
            'footer_image' => public_path('images/pie-pagina.png'),
        ])->setPaper('a4', 'portrait'); // Orientación vertical 

        // 6. DESCARGA MEDIANTE STREAM [6]
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, $title . ' - ' . now()->format('Y-m-d') . '.pdf');
    }

    // Accion para ver Afiliados del Club seleccionado
    public static function viewAfiliatesAction(): Action
    {
        return  
            Action::make('Afiliados')
                ->label('Ver Afiliados')
                ->icon('heroicon-o-users')
                ->modalHeading(fn ($record) => "Jugadores de {$record->name}")
                ->modalContent(fn ($record) =>
                    view('filament.clubs.partials.players-table', [
                        'players' => $record->players()
                            ->with('category')
                            ->orderBy('last_name')
                            ->get(),
                    ])
                )
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Cerrar');
    }
}
