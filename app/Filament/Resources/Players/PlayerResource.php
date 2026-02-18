<?php

namespace App\Filament\Resources\Players;

use App\Filament\Resources\Players\Pages\CreatePlayer;
use App\Filament\Resources\Players\Pages\EditPlayer;
use App\Filament\Resources\Players\Pages\ListPlayers;
use App\Filament\Resources\Players\Schemas\PlayerForm;
use App\Filament\Resources\Players\Tables\PlayersTable;
use App\Models\Category;
use App\Models\Player;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\GeneralRanking;

class PlayerResource extends Resource
{
    protected static ?string $model = Player::class;
    protected static string|UnitEnum|null $navigationGroup = 'Gestión Deportiva';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
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
            'logo'         => public_path('images/logo.png'),
            'footer_image' => public_path('images/pie-pagina.png'),
        ])->setPaper('a4', 'portrait'); // Orientación horizontal [6]

        // 6. DESCARGA MEDIANTE STREAM [6]
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, $title . ' - ' . now()->format('Y-m-d') . '.pdf');
    }
}
