<?php

namespace App\Filament\Resources\Tournaments;

use App\Filament\Resources\TournamentRegistrations\TournamentRegistrationResource;
use App\Filament\Resources\Tournaments\Pages\CreateTournament;
use App\Filament\Resources\Tournaments\Pages\EditTournament;
use App\Filament\Resources\Tournaments\Pages\ListTournaments;
use App\Filament\Resources\Tournaments\Schemas\TournamentForm;
use App\Filament\Resources\Tournaments\Tables\TournamentsTable;
use App\Models\Tournament;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class TournamentResource extends Resource
{
    protected static ?string $model = Tournament::class;
    protected static string|UnitEnum|null $navigationGroup = 'Torneos';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::CalendarDateRange;
    protected static ?int $navigationSort = 11;
    protected static ?string $navigationLabel = 'Gestion de Torneos';
    protected static ?string $relatedResource = TournamentRegistrationResource::class;
    protected static SubNavigationPosition|null $subNavigationPosition = SubNavigationPosition::Top; 



    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return TournamentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TournamentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            // RegistrationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            
            'index' => ListTournaments::route('/'),
            'create' => CreateTournament::route('/create'),
            'edit' => EditTournament::route('/{record}/edit'),
            'registrations' => Pages\ManageTournamentRegistrations::route('/{record}/registrations'),
            
        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\EditTournament::class,
            Pages\ManageTournamentRegistrations::class, // Aquí aparece el enlace a las inscripciones
        ]);
    }

    // Accion para inscripciones al torneo
    public static function inscriptionsAction(): Action
    {
        return
            Action::make('manageRegistrations')
                ->label(fn (Tournament $record): string =>
                    $record->registration_close_at && $record->registration_close_at->isPast()
                    ? 'Ver Inscriptos'
                    : 'Inscripciones')
                //->label('Inscripciones')
                ->icon('heroicon-o-users')
                ->color('info')
                // Genera la URL usando el nombre de la página que registraste en getPages()
                ->url(fn (Tournament $record): string => TournamentResource::getUrl('registrations', ['record' => $record])
            );
    }

    public static function resumenAction(): Action
    {
        return Action::make('resumenTorneo')
            ->label('Resumen')
            ->icon('heroicon-o-chart-bar')
            ->color('success')
            ->requiresConfirmation()
            ->action(function (Tournament $record) {

                // Obtener categorías habilitadas en el torneo
                $categorias = \App\Models\Category::whereIn('id', $record->categories)
                    ->whereNotIn('name', ['MASTER', '1ra NACIONAL'])
                    ->get();

                // Obtener inscripciones con relaciones necesarias
                $inscripciones = $record->registrations()
                    ->where('status', 'aprobado')
                    ->with(['player.category', 'player.club.city.state.federation'])
                    ->get();

                // Agrupar por federacion
                $porFederacion = $inscripciones->groupBy(function ($reg) {
                    return $reg->player->club->city->state->federation->short_name ?? 'SIN FEDERACIÓN';
                });

                // Construir matriz: provincia → categoría → cantidad
                $tabla = [];

                foreach ($porFederacion as $fed => $regs) {
                    $fila = [];

                    foreach ($categorias as $cat) {
                        $fila[$cat->name] = $regs->filter(function ($r) use ($cat) {
                            return $r->player->category_id == $cat->id;
                        })->count();
                    }

                    $fila['total'] = array_sum($fila);
                    $tabla[$fed] = $fila;
                }

                // Totales por categoría
                $totales = [];
                foreach ($categorias as $cat) {
                    $totales[$cat->name] = $inscripciones->filter(function ($r) use ($cat) {
                        return $r->player->category_id == $cat->id;
                    })->count();
                }
                $totales['total'] = array_sum($totales);

                // Render PDF
                $pdf = Pdf::loadView('pdf.resumen-torneo', [
                    'tournament' => $record,
                    'categorias' => $categorias,
                    'tabla' => $tabla,
                    'totales' => $totales,
                    'fecha' => now()->format('d/m/Y'),
                ])->setPaper('a4', 'landscape');

                return response()->streamDownload(
                    fn () => print($pdf->output()),
                    "Resumen-{$record->name}.pdf"
                );
            });
    }



}
