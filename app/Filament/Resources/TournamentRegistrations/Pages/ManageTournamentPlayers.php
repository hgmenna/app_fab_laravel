<?php

namespace App\Filament\Resources\TournamentRegistrations\Pages;

use App\Filament\Resources\TournamentRegistration\Tables\TournamentPlayersTable;
use App\Filament\Resources\TournamentRegistrations\TournamentRegistrationResource;
use BackedEnum;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use App\Filament\Resources\TournamentRegistrations\Schemas\TournamentRegistrationForm;
use Illuminate\Database\Eloquent\Builder;
use Filament\Schemas\Components\Tabs\Tab;

class ManageTournamentPlayers extends ManageRelatedRecords
{
    protected static string $resource = TournamentRegistrationResource::class;

    protected static string $relationship = 'registrations()';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public function form(Schema $schema): Schema
    {
        return TournamentRegistrationForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return TournamentPlayersTable::configure($table);
    }

     public function getTabs(): array
    {
        $tournament = $this->getOwnerRecord(); // Obtiene el modelo Tournament [5]
        $slots = $tournament->slots; // Relación de horarios

        $tabs = ['all' => Tab::make('Todos')];

        foreach ($slots as $slot) {
            $tabs[$slot->id] = Tab::make($slot->name)
                ->modifyQueryUsing(fn (Builder $query) => $query->where('slot_id', $slot->id)); // Filtro dinámico [6]
        }

        return $tabs;
    }
}
