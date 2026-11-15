<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToCompany;

class Unit extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'name', 'short_name'];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
