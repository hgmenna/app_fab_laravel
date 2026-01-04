<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tournament extends Model
{
    use HasFactory, SoftDeletes;

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
        'venue',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'registration_open_at' => 'datetime',
        'registration_close_at' => 'datetime',
        'scoring_rules' => 'array',
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
}
