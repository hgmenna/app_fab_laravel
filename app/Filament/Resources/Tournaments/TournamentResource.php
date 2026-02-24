<?php

namespace App\Filament\Resources\Tournaments;

use App\Filament\Resources\Tournaments\Pages\CreateTournament;
use App\Filament\Resources\Tournaments\Pages\EditTournament;
use App\Filament\Resources\Tournaments\Pages\ListTournaments;
use App\Filament\Resources\Tournaments\Schemas\TournamentForm;
use App\Filament\Resources\Tournaments\Tables\TournamentsTable;
use App\Models\Tournament;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;
use App\Filament\Resources\TournamentRegistrations\TournamentRegistrationResource;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Pages\Enums\SubNavigationPosition;

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

}
