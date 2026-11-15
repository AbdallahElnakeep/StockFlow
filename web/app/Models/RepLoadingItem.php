<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepLoadingItem extends Model
{
    protected $fillable = ['rep_loading_id', 'product_id', 'qty', 'unit_cost'];

    protected function casts(): array
    {
        return ['qty' => 'decimal:3', 'unit_cost' => 'decimal:4'];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
