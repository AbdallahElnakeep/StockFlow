<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToCompany;

class StockBalance extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'warehouse_id', 'product_id', 'qty', 'avg_cost'];

    protected function casts(): array
    {
        return ['qty' => 'decimal:3', 'avg_cost' => 'decimal:4'];
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
