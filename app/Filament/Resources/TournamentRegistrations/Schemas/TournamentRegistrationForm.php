<?php

namespace App\Filament\Resources\TournamentRegistrations\Schemas;

use App\Filament\Resources\TournamentRegistrations\TournamentRegistrationResource;
use App\Models\Category;
use App\Models\GeneralRanking;
use App\Models\Player;
use App\Models\Tournament;
use App\Models\TournamentModality;
use App\Models\TournamentSlot;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class TournamentRegistrationForm
{
    public static function configure(Schema $schema, ?Tournament $tournament): Schema
    {
        return $schema->components([
            Select::make('tournament_id')->label('Torneo')->relationship(
                'tournament',
                'name',
                fn ($query) => Auth::user()?->scopeDisciplineQuery($query, 'Create:TournamentRegistration'),
            )->required()->live()
                ->hidden(fn ($livewire): bool => $tournament !== null || $livewire instanceof \Filament\Resources\Pages\ManageRelatedRecords || method_exists($livewire, 'getOwnerRecord'))
                ->afterStateUpdated(function (Set $set): void {
                    $set('tournament_modality_id', null);
                    $set('player_ids', []);
                    $set('tournament_slot_id', null);
                })->dehydrated(),
            Select::make('tournament_modality_id')->label('Modalidad')
                ->options(function (Get $get, $livewire) use ($tournament): array {
                    $selected = self::resolveTournament($get, $livewire, $tournament);

                    return $selected?->tournamentModalities()->with('modality')->get()
                        ->mapWithKeys(fn (TournamentModality $item): array => [$item->id => $item->modality->name])->all() ?? [];
                })->required()->live()->native(false)
                ->afterStateUpdated(function (Set $set): void {
                    $set('player_ids', []);
                    $set('tournament_slot_id', null);
                }),
            Select::make('player_ids')->label('Integrantes')->multiple()->searchable()->preload()->required()
                ->options(fn (Get $get): array => self::eligiblePlayers($get('tournament_modality_id')))
                ->minItems(fn (Get $get): int => self::requiredPlayers($get('tournament_modality_id')))
                ->maxItems(fn (Get $get): int => self::requiredPlayers($get('tournament_modality_id')))
                ->helperText(fn (Get $get): string => 'Seleccioná exactamente '.self::requiredPlayers($get('tournament_modality_id')).' integrante(s).'),
            Select::make('tournament_slot_id')->label('Horario')->options(function (Get $get): array {
                return TournamentSlot::query()->where('tournament_modality_id', $get('tournament_modality_id'))
                    ->where('is_active', true)->orderBy('starts_at')->get()->mapWithKeys(fn (TournamentSlot $slot): array => [
                        $slot->id => $slot->name.' — '.$slot->starts_at?->format('d/m/Y H:i').' ('.($slot->max_players - $slot->occupiedPlaces()).' cupos)',
                    ])->all();
            })->native(false),
            FileUpload::make('payment_file')->label('Comprobante de pago')->disk('public_path')->visibility('public')->directory('pagos')->openable()
                ->getUploadedFileNameForStorageUsing(function (Get $get, TemporaryUploadedFile $file): string {
                    $player = Player::find(collect($get('player_ids'))->first());

                    return str(($player?->full_name ?? 'inscripcion').'-'.now()->timestamp)->slug().'.'.$file->getClientOriginalExtension();
                })
                ->visible(fn (Get $get, $livewire): bool => self::resolveTournament($get, $livewire, $tournament)?->is_payment_enabled ?? false)
                ->required(fn (Get $get, $livewire): bool => (self::resolveTournament($get, $livewire, $tournament)?->is_payment_enabled ?? false)
                    && ! (Auth::user()?->hasRole('super-admin') ?? false)),
            Select::make('status')->options(['pendiente' => 'Pendiente', 'aprobado' => 'Aprobado', 'rechazado' => 'Rechazado'])
                ->default('pendiente')->disabled(fn (): bool => ! (Auth::user()?->canGloballyOrInAnyDiscipline('UpdateStatusTournament') ?? false))->dehydrated(),
        ]);
    }

    private static function resolveTournament(Get $get, $livewire, ?Tournament $tournament): ?Tournament
    {
        if ($tournament) {
            return $tournament;
        }
        if (TournamentRegistrationResource::isNested($livewire)) {
            return $livewire->getOwnerRecord();
        }

        return Tournament::find($get('tournament_id'));
    }

    private static function requiredPlayers(mixed $configurationId): int
    {
        return max(1, (int) (TournamentModality::with('modality')->find($configurationId)?->modality?->players_per_registration ?? 1));
    }

    private static function eligiblePlayers(mixed $configurationId): array
    {
        $configuration = TournamentModality::find($configurationId);
        if (! $configuration) {
            return [];
        }
        $categories = Category::whereIn('id', $configuration->categories ?? [])->get(['id', 'code']);
        $permanent = $categories->whereNotIn('code', ['M', 'N'])->pluck('id');
        $rankingCodes = $categories->whereIn('code', ['M', 'N'])->pluck('code');

        return Player::query()->where('is_enabled_to_compete', true)->where(function ($query) use ($permanent, $rankingCodes): void {
            if ($permanent->isNotEmpty()) {
                $query->whereIn('category_id', $permanent);
            }
            if ($rankingCodes->isNotEmpty()) {
                $ranking = GeneralRanking::query()->select('player_id')->whereIn('category', $rankingCodes);
                $permanent->isNotEmpty() ? $query->orWhereIn('id', $ranking) : $query->whereIn('id', $ranking);
            }
        })->orderBy('last_name')->orderBy('first_name')->get()
            ->mapWithKeys(fn (Player $player): array => [$player->id => $player->full_name])->all();
    }
}
