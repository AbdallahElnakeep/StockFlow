<?php

namespace Tests;

use Database\Seeders\PermissionSeeder;
use App\Models\Company;
use App\Models\Customer;
use App\Models\LedgerAccount;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;
    protected function seedCompany(): array
    {
        static $sequence = 0;
        $sequence++;
        $suffix = str_pad((string) $sequence, 2, '0', STR_PAD_LEFT);

        $company = Company::create(['name' => 'شركة الاختبار '.$suffix]);
        app()->instance('current.company.id', $company->id);
        $this->seed(PermissionSeeder::class);

        $accounts = [
            ['1010', 'النقدية', 'asset'],
            ['1050', 'شيكات تحت التحصيل', 'asset'],
            ['1020', 'نقدية المندوبين', 'asset'],
            ['1100', 'العملاء', 'asset'],
            ['1200', 'المخزون', 'asset'],
            ['2100', 'الموردون', 'liability'],
            ['4000', 'إيرادات المبيعات', 'revenue'],
            ['4100', 'مردودات المبيعات', 'revenue'],
            ['5000', 'تكلفة المبيعات', 'expense'],
            ['5100', 'عجز وتالف', 'expense'],
            ['5200', 'مصروفات التوزيع', 'expense'],
        ];
        foreach ($accounts as [$code, $name, $type]) {
            LedgerAccount::create(['company_id' => $company->id, 'code' => $code, 'name' => $name, 'type' => $type, 'is_system' => true]);
        }

        $ownerRole = Role::create(['company_id' => $company->id, 'name' => 'مالك', 'is_system' => true]);
        $ownerRole->permissions()->sync(Permission::all()->pluck('id'));

        $repRole = Role::create(['company_id' => $company->id, 'name' => 'مندوب', 'is_system' => true]);

        $owner = User::create([
            'company_id' => $company->id,
            'name' => 'المالك',
            'phone' => '011'.$suffix.'0000001',
            'password' => 'password',
        ]);
        $owner->roles()->attach($ownerRole);

        $rep = User::create([
            'company_id' => $company->id,
            'name' => 'المندوب',
            'phone' => '011'.$suffix.'0000002',
            'password' => 'password',
        ]);
        $rep->roles()->attach($repRole);

        $owner = $owner->refresh();
        $rep = $rep->refresh();

        $main = Warehouse::create(['company_id' => $company->id, 'name' => 'الرئيسي', 'type' => 'main']);
        $custody = Warehouse::create(['company_id' => $company->id, 'name' => 'عهدة', 'type' => 'rep', 'rep_user_id' => $rep->id]);

        return compact('company', 'owner', 'rep', 'main', 'custody');
    }

    protected function makeProduct(Company $company, float $cost = 10, float $price = 15): Product
    {
        $unit = Unit::firstOrCreate(
            ['company_id' => $company->id, 'short_name' => 'قطعة'],
            ['name' => 'قطعة']
        );

        return Product::create([
            'company_id' => $company->id,
            'unit_id' => $unit->id,
            'name' => 'صنف اختبار '.uniqid(),
            'cost_price' => $cost,
            'sale_price' => $price,
        ]);
    }

    protected function makeCustomer(Company $company): Customer
    {
        return Customer::create(['company_id' => $company->id, 'name' => 'عميل اختبار '.uniqid(), 'credit_limit' => 100000]);
    }

    protected function makeSupplier(Company $company): Supplier
    {
        return Supplier::create(['company_id' => $company->id, 'name' => 'مورد اختبار '.uniqid()]);
    }

    protected function accountBalance(Company $company, string $code): float
    {
        $query = \App\Models\LedgerEntry::whereHas('account', fn ($q) => $q->where('code', $code));
        $debit = (clone $query)->sum('debit');
        $credit = (clone $query)->sum('credit');

        return round((float) $debit - (float) $credit, 2);
    }

    protected function balanceOf(Warehouse $warehouse, int $productId): float
    {
        return (float) (\App\Models\StockBalance::where('warehouse_id', $warehouse->id)
            ->where('product_id', $productId)
            ->value('qty') ?? 0);
    }
}
