<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToCompany;

class RepLoading extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 
        'rep_user_id', 'from_warehouse_id', 'to_warehouse_id', 'created_by',
        'uuid', 'number', 'total_cost', 'note',
    ];

    protected function casts(): array
    {
        return ['total_cost' => 'decimal:2'];
    }

    public function items()
    {
        return $this->hasMany(RepLoadingItem::class);
    }

    public function rep()
    {
        return $this->belongsTo(User::class, 'rep_user_id');
    }

    public function fromWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }
}
