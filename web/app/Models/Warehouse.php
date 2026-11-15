<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToCompany;

class Warehouse extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'name', 'type', 'rep_user_id', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function rep()
    {
        return $this->belongsTo(User::class, 'rep_user_id');
    }

    public function balances()
    {
        return $this->hasMany(StockBalance::class);
    }

    public function movements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function scopeMain($query)
    {
        return $query->where('type', 'main');
    }

    public function purchases()
    {
        return $this->hasMany(\App\Models\Purchase::class);
    }

    public function stockTransfersFrom()
    {
        return $this->hasMany(\App\Models\StockTransfer::class, 'from_warehouse_id');
    }

    public function stockTransfersTo()
    {
        return $this->hasMany(\App\Models\StockTransfer::class, 'to_warehouse_id');
    }

    public function hasStock(): bool
    {
        return $this->balances()->where('qty', '!=', 0)->exists();
    }
}
