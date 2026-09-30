<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class DisciplineModality extends Model
{
    protected $fillable = ['discipline_id', 'name', 'code', 'players_per_registration', 'order', 'is_active'];

    protected $casts = ['players_per_registration' => 'integer', 'is_active' => 'boolean'];

    public function discipline()
    {
        return $this->belongsTo(Discipline::class);
    }

    public function tournamentModalities()
    {
        return $this->hasMany(TournamentModality::class);
    }

    protected static function booted(): void
    {
        static::saving(function (self $modality): void {
            $exists = self::query()->where('discipline_id', $modality->discipline_id)
                ->where('code', $modality->code)->when($modality->exists, fn ($query) => $query->whereKeyNot($modality->id))->exists();
            if ($exists) {
                throw ValidationException::withMessages(['code' => 'El código ya existe para esta disciplina.']);
            }
        });
        static::deleting(function (self $modality): void {
            if ($modality->tournamentModalities()->exists()) {
                throw ValidationException::withMessages(['modality' => 'No se puede eliminar una modalidad utilizada por torneos.']);
            }
        });
    }
}
