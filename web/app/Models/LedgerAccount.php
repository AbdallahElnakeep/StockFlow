<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToCompany;

class LedgerAccount extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'code', 'name', 'type', 'is_system'];

    protected function casts(): array
    {
        return ['is_system' => 'boolean'];
    }

    public function entries()
    {
        return $this->hasMany(LedgerEntry::class);
    }
}
