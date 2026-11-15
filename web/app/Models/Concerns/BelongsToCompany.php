<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToCompany
{
    public static function bootBelongsToCompany(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            $companyId = self::currentCompanyId();
            if ($companyId !== null) {
                $builder->where($builder->getModel()->getTable().'.company_id', $companyId);
            }
        });

        static::creating(function ($model) {
            $companyId = self::currentCompanyId();
            if ($companyId !== null && empty($model->company_id)) {
                $model->company_id = $companyId;
            }
        });
    }

    public static function currentCompanyId(): ?int
    {
        if (app()->bound('current.company.id')) {
            return (int) app('current.company.id');
        }

        $user = auth()->user();

        if ($user && !$user->is_super_admin && $user->company_id) {
            return (int) $user->company_id;
        }

        return null;
    }
}
