<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToCompany;

class SalesInvoice extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 
        'customer_id', 'warehouse_id', 'rep_user_id', 'created_by', 'uuid', 'number',
        'type', 'channel', 'status', 'cancel_reason', 'cancelled_by', 'cancelled_at', 'subtotal', 'discount', 'total', 'total_cost',
        'collected_amount', 'note', 'issued_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'collected_amount' => 'decimal:2',
            'issued_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function items()
    {
        return $this->hasMany(SalesInvoiceItem::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function rep()
    {
        return $this->belongsTo(User::class, 'rep_user_id');
    }
}
