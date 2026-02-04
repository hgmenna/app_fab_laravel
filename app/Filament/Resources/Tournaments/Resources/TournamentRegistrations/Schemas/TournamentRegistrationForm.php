<?php

namespace App\Filament\Resources\Tournaments\Resources\TournamentRegistrations\Schemas;

use App\Models\Player;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Illuminate\Validation\ValidationException;

class TournamentRegistrationForm
{
    public static function configure(Schema $schema, $tournament): Schema
    {
        return $schema
            ->components([
                Select::make('player_id')
                ->label('Jugador')
                ->relationship('player', 'full_name')
                ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                ->searchable(['last_name', 'first_name'])
                ->preload()
                ->required(),

            Select::make('tournament_slot_id')
                ->label('Horario')
                ->options(
                    $tournament->slots
                        ->mapWithKeys(function ($slot) {
                            $count = $slot->registrations()->count();
                            $max = $slot->max_players;

                            return [
                                $slot->id => "{$slot->name} ({$count}/{$max})"
                            ];
                        })
                )
                ->required()
                ->reactive()
                ->afterStateUpdated(function ($state) use ($tournament) {
                    $slot = $tournament->slots->firstWhere('id', $state);

                    if ($slot && $slot->registrations()->count() >= $slot->max_players) {
                        throw ValidationException::withMessages([
                            'tournament_slot_id' => 'Este horario ya está completo.',
                        ]);
                    }
                }),
            Select::make('tournament_instance_id')
                ->relationship('tournamentInstance', 'description')
                ->label('Posicion')
                ->preload()
                ->live()
                ->afterStateUpdated(function ($state, callable $set) {
                    if (! $state) {
                        $set('points', null);
                        return;
                    }

                    $instance = \App\Models\TournamentInstance::find($state);

                    $set('points', $instance?->points_default ?? 0);
                }),

            TextInput::make('points')
                ->label('Puntos')
                ->disabled(),

            FileUpload::make('payment_file')
                ->label('Comprobante de pago')
                ->visible(fn () => $tournament->is_payment_enabled)
                ->required(function(callable $get) use ($tournament) {
                    if(! $tournament->is_payment_enabled) {
                        return false;
                    }

                    $playerId = $get('player_id');
                    if (! $playerId) {
                        return true; // si no se selecciono jugaor es requerido
                    }

                    $player = Player::find($playerId);
                    if (! $player) {
                        return true;
                    }

                    $categoriasNoRequeridas = ['MASTER', '1ra NACIONAL'];

                    return ! in_array($player->category->name, $categoriasNoRequeridas);
                })
                ->reactive(),
            ]);
    }
}
