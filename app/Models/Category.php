<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Validation\ValidationException;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'order',
        'discipline_id',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Category $category): void {
            $isUsedByPlayers = $category->players()->exists();
            $isUsedByTournaments = Tournament::query()
                ->where(function ($query) use ($category): void {
                    $query->whereJsonContains('categories', $category->id)
                        ->orWhereJsonContains('categories', (string) $category->id);
                })
                ->exists();

            if ($isUsedByPlayers || $isUsedByTournaments) {
                throw ValidationException::withMessages([
                    'category' => 'No se puede eliminar una categoría utilizada por jugadores o torneos.',
                ]);
            }
        });
    }

    public function players()
    {
        return $this->hasMany(Player::class);
    }

    public function tournaments()
    {
        return $this->hasMany(Tournament::class);
    }

    public function discipline()
    {
        return $this->belongsTo(Discipline::class);
    }
}
