<?php

namespace App\Filament\Resources\Tournaments\Resources\TournamentRegistrations;

use App\Filament\Resources\Tournaments\Resources\TournamentRegistrations\Pages\CreateTournamentRegistration;
use App\Filament\Resources\Tournaments\Resources\TournamentRegistrations\Pages\EditTournamentRegistration;
use App\Filament\Resources\Tournaments\Resources\TournamentRegistrations\Schemas\TournamentRegistrationForm;
use App\Filament\Resources\Tournaments\Resources\TournamentRegistrations\Tables\TournamentRegistrationsTable;
use App\Filament\Resources\Tournaments\TournamentResource;
use App\Models\TournamentRegistration;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use App\Models\GeneralRanking;

class TournamentRegistrationResource extends Resource
{
    protected static ?string $model = TournamentRegistration::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $parentResource = TournamentResource::class;
    protected static ?string $title = 'Inscripciones';
    protected static ?string $recordTitleAttribute = 'name';


    public static function form(Schema $schema): Schema
    {
        return TournamentRegistrationForm::configure($schema, null);
    }

    public static function table(Table $table): Table
    {
        return TournamentRegistrationsTable::configure($table, null);
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

    public static function exportRegistrationsToPdf($tournament)
    {
        ini_set('memory_limit', '512M');
        set_time_limit(300);

        // 1. Obtener inscripciones usando la relación correcta 'slot' definida en el modelo [3]
        // Se usa $tournament->registrations() asumiendo que el modelo Tournament tiene esta relación [1]
        $registrations = $tournament->registrations()
            ->with(['player.club', 'slot']) 
            ->get();

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
            'subtitle' => 'Nomina de Jugadores Inscriptos',
            'date'     => now()->format('d/m/Y'),
            'columns'  => $columns,
            'groups'   => $grouped,
            'logo'     => public_path('images/logo.png'),
            'footer_image' => public_path('images/pie-pagina.png'),
        ])->setPaper('a4', 'portrait');

        return response()->streamDownload(fn () => print($pdf->output()), "Inscripciones_{$tournament->name}.pdf");
    }
}
