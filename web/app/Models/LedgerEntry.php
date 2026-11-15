<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToCompany;

class LedgerEntry extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'ledger_account_id', 'ref_type', 'ref_id', 'debit', 'credit', 'entry_date'];

    protected function casts(): array
    {
        return ['debit' => 'decimal:2', 'credit' => 'decimal:2', 'entry_date' => 'datetime'];
    }

    public function account()
    {
        return $this->belongsTo(LedgerAccount::class, 'ledger_account_id');
    }
}
