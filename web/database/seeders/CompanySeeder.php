<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\LedgerAccount;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CompanySeeder extends Seeder
{
    private const ACCOUNTS = [
        ['1010', 'النقدية بالصندوق', 'asset'],
            ['1050', 'شيكات تحت التحصيل', 'asset'],
        ['1020', 'نقدية لدى المندوبين', 'asset'],
        ['1100', 'العملاء - مدينون', 'asset'],
        ['1200', 'المخزون', 'asset'],
        ['2100', 'الموردون - دائنون', 'liability'],
        ['4000', 'إيرادات المبيعات', 'revenue'],
        ['4100', 'مردودات المبيعات', 'revenue'],
        ['5000', 'تكلفة المبيعات', 'expense'],
        ['5100', 'عجز وتالف', 'expense'],
        ['5200', 'مصروفات التوزيع', 'expense'],
    ];

    public function run(): void
    {
        $company = Company::create([
            'name' => 'شركة النموذج للتوزيع',
            'phone' => '01000000000',
        ]);

        foreach (self::ACCOUNTS as [$code, $name, $type]) {
            LedgerAccount::create([
                'company_id' => $company->id,
                'code' => $code,
                'name' => $name,
                'type' => $type,
                'is_system' => true,
            ]);
        }

        $ownerRole = Role::create(['company_id' => $company->id, 'name' => 'مالك الشركة', 'is_system' => true]);
        $ownerRole->permissions()->sync(Permission::all()->pluck('id'));

        $managerRole = Role::create(['company_id' => $company->id, 'name' => 'مدير المخازن', 'is_system' => true]);
        $managerRole->permissions()->sync(
            Permission::whereIn('module', ['warehouses', 'products', 'purchases', 'inventory', 'reports'])->pluck('id')
        );

        $salesRole = Role::create(['company_id' => $company->id, 'name' => 'مسؤول المبيعات', 'is_system' => true]);
        $salesRole->permissions()->sync(
            Permission::whereIn('module', ['customers', 'sales', 'returns', 'payments', 'reports'])->pluck('id')
        );

        $accountantRole = Role::create(['company_id' => $company->id, 'name' => 'المحاسب', 'is_system' => true]);
        $accountantRole->permissions()->sync(
            Permission::whereIn('module', ['payments', 'expenses', 'reports'])->pluck('id')
                ->merge(Permission::where('module', 'reps')->where('action', 'view')->pluck('id'))
        );

        $repRole = Role::create(['company_id' => $company->id, 'name' => 'مندوب توزيع', 'is_system' => true]);
        $repRole->permissions()->sync(
            Permission::whereIn('module', ['customers', 'sales', 'returns', 'payments', 'expenses'])->where('action', 'view')->pluck('id')
        );

        $owner = User::create([
            'company_id' => $company->id,
            'name' => 'المالك',
            'phone' => '01000000001',
            'password' => Hash::make('owner123'),
        ]);
        $owner->roles()->attach($ownerRole);

        $rep = User::create([
            'company_id' => $company->id,
            'name' => 'مندوب تجريبي',
            'phone' => '01000000002',
            'password' => Hash::make('rep123'),
        ]);
        $rep->roles()->attach($repRole);

        Warehouse::create(['company_id' => $company->id, 'name' => 'المخزن الرئيسي', 'type' => 'main']);
        Warehouse::create([
            'company_id' => $company->id,
            'name' => 'عهدة - مندوب تجريبي',
            'type' => 'rep',
            'rep_user_id' => $rep->id,
        ]);
    }
}
