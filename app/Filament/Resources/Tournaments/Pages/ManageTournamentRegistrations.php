<?php

namespace App\Filament\Resources\Tournaments\Pages;

use App\Filament\Resources\TournamentRegistrations\Schemas\TournamentRegistrationForm;
use App\Filament\Resources\TournamentRegistrations\Tables\TournamentRegistrationsTable;
use App\Filament\Resources\Tournaments\TournamentResource;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ManageTournamentRegistrations extends ManageRelatedRecords
{
    protected static string $resource = TournamentResource::class;

    protected static string $relationship = 'registrations';

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $navigationLabel = 'Inscripciones';

    public function getTitle(): string
    {
        // Obtenemos el registro del torneo actual a través del "Owner Record"
        $tournament = $this->getOwnerRecord();

        // Retornamos el título concatenado con el nombre del torneo
        return 'Listado de Inscripciones - '.$tournament->name;
    }

    public function form(Schema $schema): Schema
    {

        // Pasamos el registro del torneo padre ($this->getOwnerRecord())
        return TournamentRegistrationForm::configure($schema, $this->getOwnerRecord());
    }

    public function table(Table $table): Table
    {
        return TournamentRegistrationsTable::configure($table, $this->getOwnerRecord());
    }

    public function getTabs(): array
    {
        $tournament = $this->getOwnerRecord();

        // 1. Definimos la pestaña para "Todos" los inscritos
        $tabs = [
            'all' => Tab::make('Todas las inscripciones')
                ->badge($tournament->registrations()->count()),
            // No aplicamos modifyQueryUsing para que no filtre por slot_id y muestre todo
        ];

        // 2. Generamos las pestañas por cada slot (horario) como ya lo hacías
        $slotTabs = $tournament->slots
            ->filter(fn ($slot) => $slot->starts_at !== null && $slot->max_players !== null)
            ->mapWithKeys(function ($slot) {
                $current = $slot->occupiedPlaces();
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

    public static function isNested($livewire): bool
    {
        return $livewire instanceof \Filament\Resources\RelationManagers\RelationManager ||
            $livewire instanceof \Filament\Resources\Pages\ManageRelatedRecords;
    }
}
