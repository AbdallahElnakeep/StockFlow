<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToCompany;

class RepSettlement extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 
        'rep_user_id', 'created_by', 'uuid', 'number', 'settlement_date',
        'sales_cash_total', 'sales_credit_total', 'collections_total', 'returns_total',
        'expenses_total', 'returned_stock_total', 'cash_handover', 'difference',
        'status', 'note', 'settled_at',
    ];

    protected function casts(): array
    {
        return [
            'sales_cash_total' => 'decimal:2',
            'sales_credit_total' => 'decimal:2',
            'collections_total' => 'decimal:2',
            'returns_total' => 'decimal:2',
            'expenses_total' => 'decimal:2',
            'returned_stock_total' => 'decimal:2',
            'cash_handover' => 'decimal:2',
            'difference' => 'decimal:2',
            'settlement_date' => 'date',
            'settled_at' => 'datetime',
        ];
    }

    public function rep()
    {
        return $this->belongsTo(User::class, 'rep_user_id');
    }
}
