<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Validation\ValidationException;

class Player extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'document_number',
        'document_type',
        'nationality',
        'birth_date',
        'gender',
        'email',
        'phone',
        'photo_path',
        'club_id',
        'discipline_id',
        'category_id',
        'is_active',
        'is_enabled_to_compete',
        'notes',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'is_active' => 'boolean',
        'is_enabled_to_compete' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (Player $player): void {
            if (! $player->discipline_id || ! $player->category_id) {
                return;
            }

            $categoryMatchesDiscipline = Category::query()
                ->whereKey($player->category_id)
                ->where('discipline_id', $player->discipline_id)
                ->exists();

            if (! $categoryMatchesDiscipline) {
                throw ValidationException::withMessages([
                    'category_id' => 'La categoría seleccionada no pertenece a la disciplina del jugador.',
                ]);
            }
        });
    }

    public function club()
    {
        return $this->belongsTo(Club::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function discipline()
    {
        return $this->belongsTo(Discipline::class);
    }

    public function registrations()
    {
        return $this->hasMany(TournamentRegistration::class);
    }

    public function partnerRegistrations()
    {
        return $this->hasMany(TournamentRegistration::class, 'partner_player_id');
    }

    public function disciplines()
    {
        return $this->belongsToMany(Discipline::class, 'player_discipline')
            ->withPivot('enabled_to_compete')
            ->withTimestamps();
    }

    public function affiliationLabel(): string
    {
        $this->loadMissing([
            'discipline.directFederation',
            'club.city.state.federation',
        ]);

        if (! $this->discipline) {
            return 'Sin disciplina';
        }

        $federation = $this->discipline->federationForClub($this->club);
        $disciplineName = $this->discipline->short_name ?: $this->discipline->name;
        $federationName = $federation?->short_name ?: $federation?->name;

        return $disciplineName.': '.($federationName ?: 'Sin federación');
    }

    public function memberships()
    {
        return $this->hasMany(PlayerMembership::class);
    }

    public function payments()
    {
        return $this->morphMany(Payment::class, 'payer');
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->last_name}, {$this->first_name}";
    }

    public function rankingHistories()
    {
        return $this->hasMany(RankingHistory::class);
    }

    public function categoryHistories()
    {
        return $this->hasMany(PlayerCategoryHistory::class);
    }

    public function categoryPromotions()
    {
        return $this->hasMany(PlayerCategoryPromotion::class);
    }

}
