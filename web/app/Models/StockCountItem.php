<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockCountItem extends Model
{
    protected $fillable = ['stock_count_id', 'product_id', 'system_qty', 'counted_qty', 'difference'];

    protected function casts(): array
    {
        return ['system_qty' => 'decimal:3', 'counted_qty' => 'decimal:3', 'difference' => 'decimal:3'];
    }
}
