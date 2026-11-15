<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CompanySeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Tests\TestCase;

class PermissionMatrixTest extends TestCase
{
    private array $matrix = [
        ['GET', '/dashboard', null],
        ['GET', '/users', 'users.view'],
        ['POST', '/users', 'users.create', ['name' => 'مستخدم تجريبي', 'phone' => '01777000001', 'password' => '123456']],
        ['POST', '/roles', 'roles.create', ['name' => 'دور تجريبي']],
        ['GET', '/warehouses', 'warehouses.view'],
        ['POST', '/warehouses', 'warehouses.create', ['name' => 'مخزن تجريبي', 'type' => 'main']],
        ['GET', '/products', 'products.view'],
        ['POST', '/products', 'products.create', ['name' => 'صنف تجريبي', 'cost_price' => 5, 'sale_price' => 8]],
        ['GET', '/customers', 'customers.view'],
        ['POST', '/customers', 'customers.create', ['name' => 'عميل تجريبي']],
        ['GET', '/purchases', 'purchases.view'],
        ['GET', '/purchases/create', 'purchases.create'],
        ['GET', '/invoices', 'sales.view'],
        ['GET', '/invoices/create', 'sales.create'],
        ['GET', '/returns/create', 'returns.create'],
        ['GET', '/payments', 'payments.view'],
        ['GET', '/payments/create', 'payments.create'],
        ['GET', '/reps', 'reps.view'],
        ['GET', '/loadings/create', 'reps.create'],
        ['GET', '/expenses/create', 'expenses.create'],
        ['GET', '/inventory/balances', 'inventory.view'],
        ['GET', '/transfers/create', 'inventory.create'],
        ['GET', '/counts/create', 'inventory.create'],
        ['GET', '/reports/sales', 'reports.view'],
        ['GET', '/reports/stock', 'reports.view'],
        ['GET', '/reports/cheques', 'reports.view'],
        ['GET', '/settings', 'settings.view'],
    ];

    public function test_permission_matrix_all_roles(): void
    {
        $this->seed(PermissionSeeder::class);
        $this->seed(CompanySeeder::class);

        $roles = [
            'مالك الشركة',
            'مدير المخازن',
            'مسؤول المبيعات',
            'المحاسب',
        ];

        $i = 0;
        foreach ($roles as $roleName) {
            $role = Role::where('name', $roleName)->first();
            $user = User::create([
                'company_id' => 1,
                'name' => 'مستخدم '.$roleName,
                'phone' => '0166600'.str_pad((string) ++$i, 3, '0', STR_PAD_LEFT),
                'password' => 'test1234',
            ]);
            $user->roles()->attach($role->id);
            $user = $user->refresh();
            $perms = $role->permissions()->pluck('key')->all();

            $this->withoutMiddleware(VerifyCsrfToken::class);

            foreach ($this->matrix as $row) {
                [$method, $uri] = $row;
                $needed = $row[2];
                $form = $row[3] ?? [];
                $has = $needed === null || in_array($needed, $perms, true);

                $response = $this->actingAs($user)->{$method === 'GET' ? 'get' : 'post'}($uri, $method === 'GET' ? [] : $form);
                $code = $response->getStatusCode();

                if ($has) {
                    $this->assertContains($code, [200, 302, 422], "[$roleName] $method $uri — عنده $needed لكن حصلنا $code");
                } else {
                    $this->assertSame(403, $code, "[$roleName] $method $uri — ليس عنده $needed لكن حصلنا $code (تسريب!)");
                }
            }
        }

        $this->assertTrue(true);
    }

    public function test_privilege_escalation_attempts(): void
    {
        $this->seed(PermissionSeeder::class);
        $this->seed(CompanySeeder::class);

        $salesRole = Role::where('name', 'مسؤول المبيعات')->first();
        $sales = User::create(['company_id' => 1, 'name' => 'مبيعات هاكر', 'phone' => '01666111111', 'password' => 'test1234']);
        $sales->roles()->attach($salesRole->id);
        $sales = $sales->refresh();

        $this->withoutMiddleware(VerifyCsrfToken::class);

        $this->actingAs($sales)->post('/users', ['name' => 'هاكر', 'phone' => '01777111111', 'password' => '123456'])->assertStatus(403);
        $this->actingAs($sales)->put('/users/1', ['name' => 'هاكر', 'phone' => '01000000001'])->assertStatus(403);
        $this->actingAs($sales)->post('/roles', ['name' => 'دور هاكر'])->assertStatus(403);
        $this->actingAs($sales)->get('/settings')->assertStatus(403);
        $this->actingAs($sales)->post('/locale', ['locale' => 'en'])->assertStatus(302);

        $warehouseRole = Role::where('name', 'مدير المخازن')->first();
        $wm = User::create(['company_id' => 1, 'name' => 'مخازن هاكر', 'phone' => '01666222222', 'password' => 'test1234']);
        $wm->roles()->attach($warehouseRole->id);
        $wm = $wm->refresh();

        $this->actingAs($wm)->get('/invoices/create')->assertStatus(403);
        $this->actingAs($wm)->get('/counts/create')->assertStatus(200);

        $accountantRole = Role::where('name', 'المحاسب')->first();
        $acc = User::create(['company_id' => 1, 'name' => 'محاسب هاكر', 'phone' => '01666333333', 'password' => 'test1234']);
        $acc->roles()->attach($accountantRole->id);
        $acc = $acc->refresh();

        $this->actingAs($acc)->get('/payments')->assertStatus(200);
        $this->actingAs($acc)->get('/payments/create')->assertStatus(200);
        $this->actingAs($acc)->post('/products', ['name' => 'اختراق', 'cost_price' => 1, 'sale_price' => 2])->assertStatus(403);
        $this->actingAs($acc)->get('/loadings/create')->assertStatus(403);
        $this->actingAs($acc)->get('/reps')->assertStatus(200);
    }

    public function test_guest_redirected_from_everything(): void
    {
        $this->seed(PermissionSeeder::class);
        $this->seed(CompanySeeder::class);

        foreach (['/dashboard', '/users', '/invoices', '/settings', '/reps', '/products', '/customers'] as $uri) {
            $response = $this->get($uri);
            $this->assertContains($response->getStatusCode(), [302, 401], "زائر $uri");
        }
    }
}
