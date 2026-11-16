<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToCompany;

class PurchaseReturn extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id',
        'supplier_id', 'warehouse_id', 'created_by', 'uuid', 'number', 'type', 'total', 'note', 'issued_at',
    ];

    protected function casts(): array
    {
        return ['total' => 'decimal:2', 'issued_at' => 'datetime'];
    }

    public function items()
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
