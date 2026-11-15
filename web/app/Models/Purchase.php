<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToCompany;

class Purchase extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 
        'supplier_id', 'warehouse_id', 'created_by', 'uuid', 'number',
        'payment_type', 'subtotal', 'discount', 'total', 'note', 'purchase_date',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'purchase_date' => 'date',
        ];
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
