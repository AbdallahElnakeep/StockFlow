<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToCompany;

class StockMovement extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 
        'warehouse_id', 'product_id', 'created_by', 'uuid',
        'ref_type', 'ref_id', 'qty', 'unit_cost', 'balance_after', 'note',
    ];

    protected function casts(): array
    {
        return ['qty' => 'decimal:3', 'unit_cost' => 'decimal:4', 'balance_after' => 'decimal:3'];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
