<?php

namespace App\Models;

use App\Services\ThreeCushionRankingService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class ThreeCushionStageResult extends Model
{
    protected $fillable = [
        'tournament_registration_id',
        'player_id',
        'category_id',
        'caroms',
        'innings',
        'general_average',
        'best_match_average',
        'high_run',
        'high_run_achieved_at',
    ];

    protected $casts = [
        'general_average' => 'decimal:6',
        'best_match_average' => 'decimal:6',
        'high_run_achieved_at' => 'date',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $result): void {
            $registration = TournamentRegistration::query()
                ->with(['tournament.discipline', 'participants.player.category', 'player.category'])
                ->find($result->tournament_registration_id);

            if (! $registration || ! ThreeCushionRankingService::isThreeCushion($registration->tournament?->discipline)) {
                throw ValidationException::withMessages([
                    'tournament_registration_id' => 'La inscripción no pertenece a Carambola 3 Bandas.',
                ]);
            }

            $playerIds = $registration->participantIds();

            if (count($playerIds) !== 1 || $registration->points === null) {
                throw ValidationException::withMessages([
                    'tournament_registration_id' => 'La inscripción debe ser individual y tener una posición con puntaje asignado.',
                ]);
            }

            if ((int) $result->innings <= 0) {
                throw ValidationException::withMessages([
                    'innings' => 'La cantidad de entradas debe ser mayor que cero.',
                ]);
            }

            $player = $registration->participants->first()?->player ?? $registration->player;

            if (! $player?->category_id) {
                throw ValidationException::withMessages([
                    'tournament_registration_id' => 'El jugador no tiene una categoría asignada.',
                ]);
            }

            if ((int) $result->high_run > (int) $result->caroms) {
                throw ValidationException::withMessages([
                    'high_run' => 'La serie mayor no puede superar el total de carambolas.',
                ]);
            }

            $result->player_id = $player?->id;
            $result->category_id = $player?->category_id;
            $result->general_average = round((int) $result->caroms / (int) $result->innings, 6);
            $result->high_run_achieved_at = $registration->tournament?->end_date;
        });

        static::saved(fn (self $result) => app(ThreeCushionRankingService::class)->syncForResult($result));
        static::deleted(fn (self $result) => app(ThreeCushionRankingService::class)->syncForResult($result));
    }

    public function registration()
    {
        return $this->belongsTo(TournamentRegistration::class, 'tournament_registration_id');
    }

    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
