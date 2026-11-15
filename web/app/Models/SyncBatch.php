<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToCompany;

class SyncBatch extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'user_id', 'uuid', 'device_id', 'result'];

    protected function casts(): array
    {
        return ['result' => 'array'];
    }
}
