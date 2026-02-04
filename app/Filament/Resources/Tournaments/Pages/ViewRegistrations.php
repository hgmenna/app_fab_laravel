<?php

namespace App\Filament\Resources\Tournaments\Pages;

use App\Filament\Resources\Tournaments\TournamentResource;
use Filament\Resources\Pages\Page;
use Filament\Resources\Pages\ViewRecord;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Contracts\HasTable;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;



class ViewRegistrations extends ViewRecord implements HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static string $resource = TournamentResource::class;

    //protected static string $view = 'filament.resources.tournaments.pages.view-registrations';

    public function table(Table $table): Table
    {
        $tournament = $this->record;

        return $table
            ->query(
                $tournament->registrations()->getQuery()
                    ->with(['player.club', 'player.category', 'slot'])
            )
            ->columns([
                TextColumn::make('player.full_name')
                    ->label('Jugador')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('player.club.name')
                    ->label('Club')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('player.category.name')
                    ->label('Categoría')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('slot.name')
                    ->label('Slot')
                    ->sortable(),
            /*])
            ->TableTabs([
                TableTabs::make('todos')
                //'all' => Tab::make('Todos')
                    ->badge($tournament->registrations()->count()),

                ...$tournament->slots
                    ->mapWithKeys(fn ($slot) => [
                        'slot-' . $slot->id => Tab::make($slot->name)
                            ->modifyQueryUsing(fn (Builder $query) =>
                                $query->where('tournament_slot_id', $slot->id)
                            )
                            ->badge($slot->registrations()->count()),
                    ])
                    ->toArray(),*/
            ]);

    }
    
}
