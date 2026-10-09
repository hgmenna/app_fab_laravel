<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tournament extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (Tournament $tournament): void {
            $type = $tournament->tournament_type_id
                ? TournamentType::query()->with('discipline')->find($tournament->tournament_type_id)
                : null;

            if (! $type?->usesFiveQuillasStages()) {
                $tournament->stage_number = null;
            }
        });
    }

    protected $fillable = [
        'name',
        'flyer_path',
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
        'venue_type',
        'external_venue_name',
        'external_venue_address',
        'external_venue_city',
        'external_venue_state_id',
        'non_official_logo_source',
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

    public function tournamentModalities()
    {
        return $this->hasMany(TournamentModality::class);
    }

    public function venue()
    {
        return $this->belongsTo(Club::class, 'venue_id');
    }

    public function externalVenueState(): BelongsTo
    {
        return $this->belongsTo(State::class, 'external_venue_state_id');
    }

    public function hasAssignedVenue(): bool
    {
        return $this->venue_type === 'external'
            ? filled($this->external_venue_name)
            : $this->venue_type === 'club' && filled($this->venue_id);
    }

    public function venueName(): string
    {
        return $this->venue_type === 'external'
            ? (string) ($this->external_venue_name ?: 'SIN ASIGNAR')
            : (string) ($this->venue?->name ?: 'SIN ASIGNAR');
    }

    public function venueStateId(): ?int
    {
        return $this->venue_type === 'external'
            ? $this->external_venue_state_id
            : $this->venue?->city?->state_id;
    }

    public function venueStateName(): ?string
    {
        return $this->venue_type === 'external'
            ? $this->externalVenueState?->name
            : $this->venue?->city?->state?->name;
    }

    public function venueAddress(): string
    {
        $this->loadMissing(['venue.city.state.country', 'externalVenueState.country']);

        if ($this->venue_type === 'external') {
            return implode(', ', array_filter([
                trim((string) $this->external_venue_address),
                trim((string) $this->external_venue_city),
                $this->externalVenueState?->name,
                $this->externalVenueState?->country?->name ?: 'Argentina',
            ]));
        }

        return implode(', ', array_filter([
            trim((string) $this->venue?->address),
            $this->venue?->city?->name,
            $this->venue?->city?->state?->name,
            $this->venue?->city?->state?->country?->name ?: 'Argentina',
        ]));
    }

    public function hasCompleteVenueAddress(): bool
    {
        return $this->venue_type === 'external'
            ? trim((string) $this->external_venue_address) !== ''
                && trim((string) $this->external_venue_city) !== ''
                && filled($this->external_venue_state_id)
            : trim((string) $this->venue?->address) !== ''
                && trim((string) $this->venue?->city?->name) !== ''
                && trim((string) $this->venue?->city?->state?->name) !== '';
    }

    public function regulationAudits()
    {
        return $this->hasMany(TournamentRegulationAudit::class);
    }

    public function latestSuccessfulRegulationAudit(): HasOne
    {
        return $this->hasOne(TournamentRegulationAudit::class)
            ->whereIn('result', ['approved', 'overridden'])
            ->latestOfMany();
    }

    public function regulatoryOverrideUser()
    {
        return $this->belongsTo(User::class, 'regulatory_override_by');
    }

    public function publicationLogoPath(): ?string
    {
        $this->loadMissing([
            'type.publicationFederation',
            'venue.city.state.federation',
            'externalVenueState.federation',
        ]);

        $venueFederationLogo = $this->venue_type === 'external'
            ? $this->externalVenueState?->federation?->logo_path
            : $this->venue?->city?->state?->federation?->logo_path;

        if (! $this->type?->is_official) {
            return match ($this->non_official_logo_source ?: 'venue_federation') {
                'venue_club' => $this->venue?->logo_path ?: $venueFederationLogo,
                default => $venueFederationLogo,
            };
        }

        return match ($this->type->publication_logo_source) {
            'national_federation' => $this->type->publicationFederation?->logo_path,
            'venue_federation' => $venueFederationLogo,
            default => null,
        };
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
