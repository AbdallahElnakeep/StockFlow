<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class RepCustodyService
{
    public function syncForRoles(User $user, array $roleIds): void
    {
        $hasRepRole = $this->rolesIncludeRep($roleIds);

        if ($hasRepRole) {
            $this->ensureCustody($user);
        } else {
            $this->releaseCustody($user);
        }
    }

    public function ensureCustody(User $user): Warehouse
    {
        $custody = Warehouse::where('rep_user_id', $user->id)->where('type', 'rep')->first();

        if (!$custody) {
            $custody = Warehouse::create([
                'name' => 'عهدة - '.$user->name,
                'type' => 'rep',
                'rep_user_id' => $user->id,
            ]);
        } elseif ($custody->name !== 'عهدة - '.$user->name && str_starts_with($custody->name, 'عهدة - ')) {
            $custody->update(['name' => 'عهدة - '.$user->name]);
        }

        return $custody->refresh();
    }

    public function releaseCustody(User $user): bool
    {
        $custody = Warehouse::where('rep_user_id', $user->id)->where('type', 'rep')->first();

        if (!$custody) {
            return true;
        }

        $hasStock = $custody->balances()->where('qty', '!=', 0)->exists();
        $hasOpenDocs = $user->invoices()->whereNull('rep_settlement_id')->exists()
            || $user->collections()->whereNull('rep_settlement_id')->exists();

        if ($hasStock || $hasOpenDocs) {
            return false;
        }

        $custody->update(['is_active' => false]);

        return true;
    }

    public function rolesIncludeRep(array $roleIds): bool
    {
        if (empty($roleIds)) {
            return false;
        }

        return Role::whereIn('id', $roleIds)
            ->where(fn ($q) => $q->where('name', 'like', '%مندوب%')->orWhere('name', 'like', '%Rep%'))
            ->exists();
    }

    public function hasActiveCustody(User $user): bool
    {
        return $user->custodyWarehouse()->where('is_active', true)->exists();
    }
}
