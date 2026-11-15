<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = ['name', 'phone', 'plan', 'is_active', 'trial_ends_at'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'trial_ends_at' => 'date',
        ];
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
