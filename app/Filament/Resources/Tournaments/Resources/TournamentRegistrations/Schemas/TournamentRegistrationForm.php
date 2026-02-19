<?php

namespace App\Filament\Resources\Tournaments\Resources\TournamentRegistrations\Schemas;

use App\Models\Player;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use App\Models\Tournament;
use Filament\Schemas\Components\Utilities\Get;
use App\Models\TournamentInstance;
use Illuminate\Support\Facades\Auth;

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
                ->required()
                ->live(),
            Select::make('tournament_slot_id')
                ->label('Horario')
                ->options(function (Get $get) use ($tournament) {
                    // Si no hay objeto $tournament, lo buscamos por el ID del formulario
                    $t = $tournament ?? Tournament::find($get('tournament_id'));
                    if (!$t) return [];
                    $user = Auth::user();

                    return $t->slots
                        ->when($user->name !== 'super-admin',
                            fn($slots) => $slots->filter(
                                fn($slot) => $slot->registrations()->count() < $slot->max_players
                            )
                        )
                        //->filter(fn ($slot) => $slot->registrations()->count() < $slot->max_players)
                        ->mapWithKeys(fn ($slot) => [
                        $slot->id => "{$slot->name} ({$slot->registrations()->count()}/{$slot->max_players})"
                    ]);
                })
                ->required()
                ->live(),
            Select::make('tournament_instance_id')
                ->relationship('tournamentInstance', 'description')
                ->label('Posicion')
                ->visible(fn () => Auth::user()->can('EditField') && $tournament->start_date < now())
                ->preload()
                ->live()
                ->afterStateUpdated(function ($state, callable $set) {
                    if (! $state) {
                        $set('points', null);
                        return;
                    }

                    $instance = TournamentInstance::find($state);

                    $set('points', $instance?->points_default ?? 0);
                }),

            TextInput::make('points')
                ->label('Puntos')
                ->visible(fn () => Auth::user()->can('EditField') && $tournament->start_date < now())
                ->disabled(),

            FileUpload::make('payment_file')
                ->label('Comprobante de pago')
                // Usamos una función anónima para verificar la visibilidad dinámicamente
                ->visible(function (Get $get) use ($tournament) {
                    $t = $tournament ?? Tournament::find($get('tournament_id'));
                    return $t?->is_payment_enabled ?? false;
                })
                ->required(function(Get $get) use ($tournament) {
                    $t = $tournament ?? Tournament::find($get('tournament_id'));
                    
                    if(!$t || !$t->is_payment_enabled) {
                        return false;
                    }

                    $playerId = $get('player_id');
                    if (!$playerId) return true;

                    $player = Player::find($playerId);
                    if (!$player) return true;

                    $categoriasNoRequeridas = ['MASTER', '1ra NACIONAL'];
                    return ! in_array($player->category->name, $categoriasNoRequeridas);
                })
                ->live(),
            ]);
    }
}
