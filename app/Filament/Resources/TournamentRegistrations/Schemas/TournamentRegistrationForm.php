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
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\FileUpload;

class TournamentRegistrationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Torneo (preseleccionado y bloqueado si viene desde la tabla)
            Select::make('tournament_id')
                ->label('Torneo')
                ->options(
                    Tournament::query()
                        ->where('registration_open_at', '<=', now())
                        ->where('registration_close_at', '>=', now())
                        ->where('start_date', '>', now())
                        ->pluck('name', 'id')
                )
                ->default(fn () => request('tournament_id'))
                ->disabled(fn () => request()->has('tournament_id'))
                ->required()
                ->reactive(),

            // Jugador
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

            // Horarios con cupos disponibles
            Select::make('tournament_slot_id')
                ->label('Horario')
                ->relationship('slots', 'name')
                ->options(function (callable $get) {
                    $tournamentId = $get('tournament_id');

                    if (! $tournamentId) {
                        return [];
                    }

                    return TournamentSlot::query()
                        ->where('tournament_id', $tournamentId)
                        ->where('is_active', true)
                        ->get()
                        ->filter(fn ($slot) =>
                            $slot->registrations()->count() < $slot->max_players
                        )
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

           FileUpload::make('payment_file')
                ->label('Comprobante de pago')
                ->directory('payments')
                ->visible(fn (callable $get) => $get('tournament_id')
                    ? Tournament::find($get('tournament_id'))?->payment_enabled
                    : false
                )
                ->required(fn (callable $get) => $get('tournament_id')
                    ? Tournament::find($get('tournament_id'))?->payment_enabled
                    : false
                )
                ->dehydrated(fn (callable $get) => $get('tournament_id')
                    ? Tournament::find($get('tournament_id'))?->payment_enabled
                    : false
                ),


            Section::make('Información')
                ->description('Solo se muestran torneos habilitados y horarios con cupos disponibles.')
                ->collapsible(false),

        ]);
    }
}
