<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tournament extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'discipline_id',
        'tournament_type_id',
        'federation_id',
        'category_id',
        'start_date',
        'end_date',
        'status',
        'regulatory_override',
        'regulatory_override_reason',
        'regulatory_override_by',
        'regulatory_override_at',
        'manual_route_checks',
        'scoring_rules',
        'registration_open_at',
        'registration_close_at',
        'registration_enabled',
        'entry_fee',
        'venue_id',
        'notes',
        'categories',
        'is_payment_enabled',
        'is_active',
        'stage_number',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'registration_open_at' => 'datetime',
        'registration_close_at' => 'datetime',
        'registration_enabled' => 'boolean',
        'regulatory_override' => 'boolean',
        'regulatory_override_at' => 'datetime',
        'manual_route_checks' => 'array',
        'scoring_rules' => 'array',
        'categories' => 'array',
        'is_payment_enabled' => 'boolean',
    ];

    public function discipline()
    {
        return $this->belongsTo(Discipline::class);
    }

    public function type()
    {
        return $this->belongsTo(TournamentType::class, 'tournament_type_id');
    }

    public function federation()
    {
        return $this->belongsTo(Federation::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function registrations()
    {
        return $this->hasMany(TournamentRegistration::class);
    }

    public function slots()
    {
        return $this->hasMany(TournamentSlot::class);
    }

    public function categoryPrices()
    {
        return $this->hasMany(TournamentCategoryPrice::class);
    }

    public function venue()
    {
        return $this->belongsTo(Club::class, 'venue_id');
    }

    public function regulationAudits()
    {
        return $this->hasMany(TournamentRegulationAudit::class);
    }

    public function regulatoryOverrideUser()
    {
        return $this->belongsTo(User::class, 'regulatory_override_by');
    }

    public function scopeAvailableForRegistration($query)
    {
        return $query
            ->where('registration_enabled', true)
            ->whereDate('registration_open_at', '<=', today('America/Argentina/Buenos_Aires'))
            ->whereDate('registration_close_at', '>=', today('America/Argentina/Buenos_Aires'))
            ->where('start_date', '>', now());
    }

    public function isRegistrationOpen(): bool
    {
        if (! $this->registration_enabled || ! $this->registration_open_at || ! $this->registration_close_at) {
            return false;
        }

        $today = now('America/Argentina/Buenos_Aires')->toDateString();

        return $this->registration_open_at->toDateString() <= $today
            && $this->registration_close_at->toDateString() >= $today;
    }

    public function player()
    {
        return $this->belongsTo(Player::class);
    }
}
