<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TournamentModality extends Model
{
    protected $fillable = ['tournament_id', 'discipline_modality_id', 'categories'];

    protected $casts = ['categories' => 'array'];

    public function tournament()
    {
        return $this->belongsTo(Tournament::class);
    }

    public function modality()
    {
        return $this->belongsTo(DisciplineModality::class, 'discipline_modality_id');
    }

    public function categoryPrices()
    {
        return $this->hasMany(TournamentCategoryPrice::class);
    }

    public function slots()
    {
        return $this->hasMany(TournamentSlot::class);
    }

    public function registrations()
    {
        return $this->hasMany(TournamentRegistration::class);
    }

    protected static function booted(): void
    {
        $sync = function (self $configuration): void {
            $tournament = $configuration->tournament;
            if ($tournament) {
                $tournament->forceFill([
                    'categories' => $tournament->tournamentModalities()->get()
                        ->flatMap(fn (self $item) => $item->categories ?? [])->unique()->values()->all(),
                ])->saveQuietly();
            }
        };
        static::saved($sync);
        static::deleted($sync);
    }
}
