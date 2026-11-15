<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToCompany;

class StockCount extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 
        'warehouse_id', 'created_by', 'uuid', 'number', 'status', 'note', 'adjusted_at',
    ];

    protected function casts(): array
    {
        return ['adjusted_at' => 'datetime'];
    }

    public function items()
    {
        return $this->hasMany(StockCountItem::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
