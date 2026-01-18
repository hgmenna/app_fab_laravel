<?php

namespace App\Filament\Widgets;

use App\Models\TournamentRegistration;
use Filament\Tables;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;

class SlotRegistrationsWidget extends TableWidget
{
    protected static ?string $heading = null;

    public ?int $slotId = null;
    public ?int $tournamentId = null;

    protected function getTableQuery(): ?Builder
    {
        return TournamentRegistration::query()
            ->where('tournament_id', $this->tournamentId)
            ->where('slot_id', $this->slotId)
            ->with(['player.club', 'player.category']);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('player.name')
                ->label('Jugador')
                ->sortable()
                ->searchable(),

            TextColumn::make('player.club.name')
                ->label('Club'),

            TextColumn::make('player.category.name')
                ->label('Categoría'),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            EditAction::make()
                ->url(fn ($record) =>
                    route('filament.admin.resources.tournament-registrations.edit', $record)
                ),

            DeleteAction::make(),
        ];
    }
}