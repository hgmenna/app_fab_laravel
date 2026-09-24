<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class Discipline extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'short_name',
        'description',
        'scoring_rules',
        'active',
        'affiliation_mode',
        'direct_federation_id',
    ];

    protected $casts = [
        'scoring_rules' => 'array',
        'active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (Discipline $discipline): void {
            if ($discipline->affiliation_mode === 'direct' && ! $discipline->direct_federation_id) {
                throw ValidationException::withMessages([
                    'direct_federation_id' => 'Seleccioná la federación para la afiliación directa.',
                ]);
            }

            if ($discipline->affiliation_mode !== 'direct') {
                $discipline->direct_federation_id = null;
            }
        });
    }

    public function players(): BelongsToMany
    {
        return $this->belongsToMany(Player::class, 'player_discipline')
            ->withPivot('enabled_to_compete')
            ->withTimestamps();
    }

    public function tournamentTypes(): HasMany
    {
        return $this->hasMany(TournamentType::class);
    }

    public function tournaments(): HasMany
    {
        return $this->hasMany(Tournament::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function primaryPlayers(): HasMany
    {
        return $this->hasMany(Player::class);
    }

    public function directFederation()
    {
        return $this->belongsTo(Federation::class, 'direct_federation_id');
    }

    public function federationForClub(?Club $club): ?Federation
    {
        if ($this->affiliation_mode === 'direct') {
            return $this->directFederation;
        }

        return $club?->city?->state?->federation;
    }
}
