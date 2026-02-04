<?php

namespace App\Filament\Resources\Tournaments\RelationManagers;

use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Illuminate\Validation\ValidationException;
use Filament\Forms\Components\FileUpload;
use App\Filament\Resources\Tournaments\Resources\TournamentRegistrations\Schemas\TournamentRegistrationForm;
use App\Filament\Resources\Tournaments\Resources\TournamentRegistrations\Tables\TournamentRegistrationsTable;
use Filament\Schemas\Components\Tabs\Tab;

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

        return $tournament->slots
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
    }

}
