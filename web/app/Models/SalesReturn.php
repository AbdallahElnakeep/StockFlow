<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToCompany;

class SalesReturn extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 
        'customer_id', 'sales_invoice_id', 'warehouse_id', 'rep_user_id', 'created_by',
        'uuid', 'number', 'channel', 'restock', 'total', 'note', 'issued_at',
    ];

    protected function casts(): array
    {
        return [
            'restock' => 'boolean',
            'total' => 'decimal:2',
            'issued_at' => 'datetime',
        ];
    }

    public function items()
    {
        return $this->hasMany(SalesReturnItem::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoice()
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }
}
