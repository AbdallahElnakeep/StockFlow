<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\RepCustodyService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $companyId = \App\Models\Company::orderBy('id')->value('id');
        if (!$companyId) {
            return;
        }
        app()->instance('current.company.id', $companyId);

        $accounts = [
            ['مدير المخازن (تجريبي)', '01500000001', 'manger123', 'مدير المخازن'],
            ['مسؤول المبيعات (تجريبي)', '01500000002', 'sales123', 'مسؤول المبيعات'],
            ['المحاسب (تجريبي)', '01500000003', 'account123', 'المحاسب'],
        ];

        foreach ($accounts as [$name, $phone, $password, $roleName]) {
            $role = Role::where('company_id', $companyId)->where('name', $roleName)->first();
            if (!$role) {
                continue;
            }

            $user = User::firstOrCreate(
                ['phone' => $phone],
                [
                    'company_id' => $companyId,
                    'name' => $name,
                    'password' => Hash::make($password),
                ]
            );
            if (!$user->roles()->where('role_id', $role->id)->exists()) {
                $user->roles()->attach($role->id);
            }
        }

        $repRole = Role::where('company_id', $companyId)->where('name', 'مندوب توزيع')->first();
        if ($repRole) {
            $gizaRep = User::firstOrCreate(
                ['phone' => '01000000003'],
                ['company_id' => $companyId, 'name' => 'مندوب الجيزة', 'password' => Hash::make('rep123')]
            );
            if (!$gizaRep->roles()->where('role_id', $repRole->id)->exists()) {
                $gizaRep->roles()->attach($repRole->id);
            }
            app(RepCustodyService::class)->ensureCustody($gizaRep->refresh());
        }

        $this->command?->info('حسابات تجريبية جاهزة: المالك/مندوبان/مدير مخازن/مسؤول مبيعات/محاسب');
    }
}
