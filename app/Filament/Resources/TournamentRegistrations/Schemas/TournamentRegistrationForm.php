<?php

namespace App\Filament\Resources\TournamentRegistrations\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use App\Models\Tournament;
use App\Models\Player;
use App\Models\TournamentSlot;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Infolists\Components\TextEntry;

class TournamentRegistrationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tournament_id')
                ->label('Torneo')
                ->options(
                    Tournament::query()
                        ->where('status', 'active')
                        ->where('registration_open_at', '<=', now())
                        ->where('registration_close_at', '>=', now())
                        ->where('start_date', '>', now())
                        ->orderBy('start_date')
                        ->pluck('name', 'id')
                )
                ->searchable()
                ->required()
                ->reactive(),

            Select::make('player_id')
                ->label('Jugador')
                ->options(
                    Player::query()
                        ->where('is_active', true)
                        ->where('is_enabled_to_compete', true)
                        ->orderBy('last_name')
                        ->orderBy('first_name')
                        ->get()
                        ->pluck('full_name', 'id')
                )
                ->searchable()
                ->required(),

            Select::make('tournament_slot_id')
                ->label('Horario')
                ->options(function (Get $get) {
                    $tournamentId = $get('tournament_id');

                    if (! $tournamentId) {
                        return [];
                    }

                    return TournamentSlot::query()
                        ->where('tournament_id', $tournamentId)
                        ->where('is_active', true)
                        ->get()
                        ->filter(fn ($slot) => $slot->registrations()->count() < $slot->max_players)
                        ->mapWithKeys(function ($slot) {
                            $used = $slot->registrations()->count();
                            $available = $slot->max_players - $used;

                            return [
                                $slot->id => $slot->starts_at->format('d/m H:i') . " (Cupos: {$available})",
                            ];
                        });
                })
                ->searchable()
                ->required()
                ->reactive(),

           TextEntry::make('info')
                ->label('')
                ->default('Solo se muestran torneos habilitados y horarios con cupos disponibles.')
                ->columnSpanFull()
        ]);
    }
}
