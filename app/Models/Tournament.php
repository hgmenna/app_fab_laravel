<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tournament extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'tournament_type_id',
        'federation_id',
        'category_id',
        'start_date',
        'end_date',
        'venue',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

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

    public function slots()
    {
        return $this->hasMany(TournamentSlot::class);
    }

    public function registrations()
    {
        return $this->hasMany(TournamentRegistration::class);
    }

    public function categoryPrices()
    {
        return $this->hasMany(TournamentCategoryPrice::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'tournament_category_price')
            ->withPivot('price')
            ->withTimestamps();
    }


}
