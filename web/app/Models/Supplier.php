<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToCompany;

class Supplier extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'name', 'phone', 'address', 'note', 'balance', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'balance' => 'decimal:2'];
    }
}
