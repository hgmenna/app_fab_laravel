<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
        'scoring_rules',
        'registration_open_at',
        'registration_close_at',
        'entry_fee',
        'venue_id',
        'notes',
        'categories',
        'is_payment_enabled',
        'is_active',
        'stage_number'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'registration_open_at' => 'datetime',
        'registration_close_at' => 'datetime',
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

    public function scopeAvailableForRegistration($query)
    {
        return $query
            ->where('registration_open_at', '<=', now())
            ->where('registration_close_at', '>=', now())
            ->where('start_date', '>', now());
    }

    public function player() 
    {
        return $this->belongsTo(Player::class);
    }


}
