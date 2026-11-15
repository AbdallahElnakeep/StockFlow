<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'company_id', 'name', 'phone', 'password', 'is_active', 'is_super_admin', 'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_super_admin' => 'boolean',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function custodyWarehouse()
    {
        return $this->hasOne(Warehouse::class, 'rep_user_id');
    }

    public function hasPermission(string $key): bool
    {
        if ($this->is_super_admin) {
            return true;
        }

        return $this->roles()
            ->whereHas('permissions', fn ($q) => $q->where('key', $key))
            ->exists();
    }

    public function isRep(): bool
    {
        return $this->custodyWarehouse()->exists();
    }

    public function invoices()
    {
        return $this->hasMany(SalesInvoice::class, 'rep_user_id');
    }

    public function collections()
    {
        return $this->hasMany(Payment::class, 'rep_user_id');
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class, 'rep_user_id');
    }
}
