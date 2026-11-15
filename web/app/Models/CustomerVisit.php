<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToCompany;

class CustomerVisit extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'rep_user_id', 'customer_id', 'uuid', 'status', 'note', 'visited_at'];

    protected function casts(): array
    {
        return ['visited_at' => 'datetime'];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
