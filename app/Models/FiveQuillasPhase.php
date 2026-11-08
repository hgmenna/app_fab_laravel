<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Etapa configurable de la competencia con su método de ingreso, reglas de juego y horarios.
 */
class FiveQuillasPhase extends Model
{
    protected $fillable = ['competition_id', 'code', 'name', 'phase_type', 'sort_order', 'sets_to_play', 'points_per_set', 'seeding_method', 'split_by_slot', 'is_enabled', 'status', 'is_public', 'settings'];
    protected $casts = ['split_by_slot' => 'boolean', 'is_enabled' => 'boolean', 'is_public' => 'boolean', 'settings' => 'array'];

    public function scheduleRevision(): string
    {
        return hash('sha256', serialize([
            $this->id,
            data_get($this->settings, 'schedules'),
            data_get($this->settings, 'tables'),
            data_get($this->settings, 'estimated_minutes'),
        ]));
    }

    /** Reconoce fases que ya tienen actividad pero conservan un estado anterior. */
    public function displayStatus(): string
    {
        if (! in_array($this->status, ['ready', 'pending', 'draft'], true)) {
            return $this->status;
        }

        $activeStatuses = ['called', 'in_progress'];
        $hasActiveMatch = $this->relationLoaded('matches')
            ? $this->matches->whereIn('status', $activeStatuses)->contains(fn (FiveQuillasMatch $match): bool => ! $match->is_bye)
            : $this->matches()->whereIn('status', $activeStatuses)->where('is_bye', false)->exists();

        return $hasActiveMatch ? 'in_progress' : $this->status;
    }

    /** Identifica la final por el partido y por su lugar en el formato del torneo. */
    public function isTournamentFinal(FiveQuillasMatch $match): bool
    {
        return $this->phase_type === 'bracket'
            && (int) data_get($this->settings, 'qualifiers', 0) <= 1
            && $match->next_match_id === null
            && preg_match('/^final(?:\s+\d+)?$/i', trim((string) $match->label)) === 1
            && self::query()->where('competition_id', $this->competition_id)
                ->where('is_enabled', true)->where('sort_order', '>', $this->sort_order)->doesntExist();
    }

    public function competition() { return $this->belongsTo(FiveQuillasCompetition::class, 'competition_id'); }
    public function groups() { return $this->hasMany(FiveQuillasGroup::class, 'phase_id')->orderBy('sort_order'); }
    public function matches() { return $this->hasMany(FiveQuillasMatch::class, 'phase_id')->orderBy('round_number')->orderBy('bracket_order'); }
}
