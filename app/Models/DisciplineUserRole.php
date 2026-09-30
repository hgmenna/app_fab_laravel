<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DisciplineUserRole extends Model
{
    protected $fillable = ['user_id', 'discipline_id', 'role_id', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function discipline()
    {
        return $this->belongsTo(Discipline::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
