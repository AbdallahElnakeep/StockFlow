<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToCompany;

class Customer extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 
        'name', 'phone', 'address', 'area', 'credit_limit', 'balance', 'is_active', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'credit_limit' => 'decimal:2',
            'balance' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function invoices()
    {
        return $this->hasMany(SalesInvoice::class);
    }
}
