<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToCompany;

class Payment extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id',
        'rep_user_id', 'created_by', 'uuid', 'number', 'payable_type', 'payable_id',
        'direction', 'method', 'cheque_no', 'due_date', 'amount', 'note', 'issued_at',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'issued_at' => 'datetime'];
    }

    public function rep()
    {
        return $this->belongsTo(User::class, 'rep_user_id');
    }

    public function payable()
    {
        return $this->morphTo();
    }
}
