<?php

namespace App\Filament\Resources\Tournaments\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Filament\Resources\Tournaments\Resources\TournamentRegistrations\Schemas\TournamentRegistrationForm;
use App\Filament\Resources\Tournaments\Resources\TournamentRegistrations\Tables\TournamentRegistrationsTable;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Contracts\Support\Htmlable;

class RegistrationsRelationManager extends RelationManager
{
    protected static string $relationship = 'registrations';

    public function form(Schema $schema): Schema
    {

        return TournamentRegistrationForm::configure($schema, $this->ownerRecord);

    }

    public function table(Table $table): Table
    {
        $tournament = $table->getLivewire()->ownerRecord;
        return TournamentRegistrationsTable::configure($table, $tournament);
            
    }
    

    public function getTabs(): array
    {
        $tournament = $this->ownerRecord;

         // 1. Definimos la pestaña para "Todos" los inscritos
        $tabs = [
            'all' => Tab::make('Todos los Inscritos')
                ->badge($tournament->registrations()->count())
                // No aplicamos modifyQueryUsing para que no filtre por slot_id y muestre todo
        ];

        // 2. Generamos las pestañas por cada slot (horario) como ya lo hacías
        $slotTabs = $tournament->slots
            ->mapWithKeys(function ($slot) {
                $current = $slot->registrations()->count();
                $max = $slot->max_players;

                return [
                    "slot_{$slot->id}" => Tab::make($slot->name)
                        ->badge("{$current} / {$max}")
                        ->modifyQueryUsing(fn ($query) => $query->where('tournament_slot_id', $slot->id)),
                ];
            })
            ->toArray();

        // 3. Combinamos ambas partes: la pestaña general y las específicas
        return array_merge($tabs, $slotTabs);
    }

   public function getTableHeading(): string|Htmlable|null
   {
        return 'Inscripciones';
   }

}
