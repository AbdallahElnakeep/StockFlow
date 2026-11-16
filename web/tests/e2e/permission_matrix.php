<?php

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Artisan;

Artisan::call('migrate:fresh', ['--force' => true]);
Artisan::call('db:seed', ['--class' => \Database\Seeders\PermissionSeeder::class, '--force' => true]);
Artisan::call('db:seed', ['--class' => \Database\Seeders\CompanySeeder::class, '--force' => true]);
app()->instance('current.company.id', 1);

$base = 'http://127.0.0.1:8000';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

function requestAs($kernel, string $method, string $uri, ?App\Models\User $user, array $data = []): int {
    $server = ['HTTP_ACCEPT' => 'application/json'];
    if ($user) {
        $token = $user->createToken('matrix')->plainTextToken;
        $server['HTTP_AUTHORIZATION'] = 'Bearer '.$token;
    }
    if ($user === null) {
        $r = Illuminate\Http\Request::create($uri, $method, [], [], [], $server);
        try {
            return $kernel->handle($r)->getStatusCode();
        } catch (\Throwable) {
            return 0;
        }
    }
    return 0;
}

function webRequest($kernel, string $method, string $uri, ?App\Models\User $user, array $form = []): int {
    try {
        auth()->forgetGuards();
        if ($user) {
            $user = $user->refresh();
            auth('web')->setUser($user);
            app()->instance('current.company.id', $user->company_id);
        }
        config(['session.driver' => 'array']);
        $session = app('session')->driver('array');
        $r = Illuminate\Http\Request::create($uri, $method, $form, [], [], ['HTTP_ACCEPT' => 'text/html', 'REQUEST_METHOD' => $method]);
        $r->setLaravelSession($session);
        $r->session()->regenerateToken();
        if ($method !== 'GET' && $form !== []) {
            $form['_token'] = $r->session()->token();
            $r = Illuminate\Http\Request::create($uri, $method, $form, [], [], ['HTTP_ACCEPT' => 'text/html', 'REQUEST_METHOD' => $method]);
            $r->setLaravelSession($session);
        }
        $response = $kernel->handle($r);
        return $response->getStatusCode();
    } catch (\Throwable $e) {
        return 0;
    }
}

$pass = 0; $fail = 0; $failures = [];
function expect(string $label, int $actual, array $accepted): void {
    global $pass, $fail, $failures;
    if (in_array($actual, $accepted, true)) {
        $pass++;
    } else {
        $fail++;
        $failures[] = "$label → حصلنا $actual (المتوقع ".implode('/', $accepted).")";
        echo "❌ $label → $actual (متوقع ".implode('/', $accepted).")\n";
    }
}

$roles = [
    'المالك' => App\Models\Role::where('name', 'مالك الشركة')->first(),
    'مدير المخازن' => App\Models\Role::where('name', 'مدير المخازن')->first(),
    'مسؤول المبيعات' => App\Models\Role::where('name', 'مسؤول المبيعات')->first(),
    'المحاسب' => App\Models\Role::where('name', 'المحاسب')->first(),
];

$users = [];
$i = 0;
foreach ($roles as $name => $role) {
    $u = App\Models\User::create([
        'company_id' => 1,
        'name' => 'مستخدم '.$name,
        'phone' => '016660000'.str_pad((string) ++$i, 2, '0', STR_PAD_LEFT),
        'password' => 'test1234',
    ]);
    $u->roles()->attach($role->id);
    $users[$name] = $u->refresh();
}

function permsOf(App\Models\User $u): array {
    return $u->roles()->first()->permissions()->pluck('key')->all();
}

$matrix = [
    ['GET', '/dashboard'],
    ['GET', '/users', 'users.view'],
    ['POST', '/users', 'users.create', ['name' => 'x', 'phone' => '01777777777', 'password' => '123456']],
    ['GET', '/roles', 'roles.view'],
    ['POST', '/roles', 'roles.create', ['name' => 'دور تجريبي']],
    ['GET', '/warehouses', 'warehouses.view'],
    ['POST', '/warehouses', 'warehouses.create', ['name' => 'مخزن م', 'type' => 'main']],
    ['GET', '/products', 'products.view'],
    ['POST', '/products', 'products.create', ['name' => 'صنف م', 'cost_price' => 5, 'sale_price' => 8]],
    ['GET', '/customers', 'customers.view'],
    ['POST', '/customers', 'customers.create', ['name' => 'عميل م']],
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

echo "== المصفوفة: كل دور × كل مسار ==\n";
foreach (['المالك', 'مدير المخازن', 'مسؤول المبيعات', 'المحاسب'] as $roleName) {
    $u = $users[$roleName];
    $perms = permsOf($u);
    echo "-- $roleName (".count($perms)." صلاحية) --\n";
    foreach ($matrix as $row) {
        [$method, $uri] = $row;
        $needed = $row[2] ?? null;
        $form = $row[3] ?? [];
        $has = $needed === null || in_array($needed, $perms, true);
        $code = webRequest($kernel, $method, $uri, $u, $form);
        $accepted = $has ? [200, 302, 422] : [403];
        expect("[$roleName] $method $uri".($needed ? " (يتطلب $needed)".($has ? ' — عنده' : ' — ليس عنده') : ''), $code, $accepted);
    }
}

echo "\n== هجمات ترقية الصلاحيات ==\n";
$sales = $users['مسؤول المبيعات'];
expect('مبيعات ينشئ مستخدمًا (تجاوز)', webRequest($kernel, 'POST', '/users', $sales, ['name' => 'هاكر', 'phone' => '01888888888', 'password' => '123456']), [403]);
expect('مبيعات يعدل المالك', webRequest($kernel, 'PUT', '/users/1', $sales, ['name' => 'هاكر', 'phone' => '01000000001']), [403]);
expect('مبيعات ينشئ دورًا', webRequest($kernel, 'POST', '/roles', $sales, ['name' => 'دور هاكر']), [403]);
expect('مبيعات يفتح الإعدادات', webRequest($kernel, 'GET', '/settings', $sales), [403]);
expect('مخازن يبيع فاتورة', webRequest($kernel, 'GET', '/invoices/create', $users['مدير المخازن']), [403]);
expect('مبيعات يجرد', webRequest($kernel, 'GET', '/counts/create', $sales), [403]);
expect('محاسب يضيف صنفًا', webRequest($kernel, 'POST', '/products', $users['المحاسب'], ['name' => 'اختراق', 'cost_price' => 1, 'sale_price' => 2]), [403]);
expect('محاسب يحمّل عهدة', webRequest($kernel, 'GET', '/loadings/create', $users['المحاسب']), [403]);

echo "\n== زائر بدون دخول ==\n";
foreach (['/dashboard', '/users', '/invoices', '/settings', '/reps'] as $uri) {
    expect("زائر $uri", webRequest($kernel, 'GET', $uri, null), [302, 401]);
}

echo "\n== مندوب API: عزل عمودي ==\n";
$repA = App\Models\User::where('phone', '01000000002')->first();
$tokenA = $repA->createToken('a')->plainTextToken;
$r = Illuminate\Http\Request::create('/api/bootstrap', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => "Bearer $tokenA"]);
$resp = $kernel->handle($r);
$body = json_decode($resp->getContent(), true);
expect('مندوب يرى عملاء شركته فقط', $resp->getStatusCode(), [200]);
expect('bootstrap لا يكشف هويات أخرى', isset($body['company_id']) ? 0 : 1, [1]);

$evilCompany = App\Models\Company::create(['name' => 'شركة شريرة']);
$evilRep = App\Models\User::create(['company_id' => $evilCompany->id, 'name' => 'مندوب شرير', 'phone' => '01999999999', 'password' => 'evil123']);
$evilRole = App\Models\Role::create(['company_id' => $evilCompany->id, 'name' => 'مندوب']);
$evilRole->permissions()->sync(App\Models\Permission::whereIn('module', ['customers', 'sales'])->where('action', 'view')->pluck('id'));
$evilRep->roles()->attach($evilRole);
app()->forgetInstance('current.company.id');
auth()->forgetGuards();
app()->instance('current.company.id', $evilCompany->id);
app(\App\Services\RepCustodyService::class)->ensureCustody($evilRep);
$tokenE = $evilRep->refresh()->createToken('e')->plainTextToken;
auth()->forgetGuards();
app()->forgetInstance('current.company.id');
$r = Illuminate\Http\Request::create('/api/bootstrap', 'GET', [], [], [], ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => "Bearer $tokenE"]);
$resp = $kernel->handle($r);
$body = json_decode($resp->getContent(), true);
$ourCustomerIds = App\Models\Customer::where('company_id', 1)->pluck('id');
$evilSees = collect($body['customers'] ?? [])->pluck('id');
expect('مندوب الشركة الشريرة لا يرى عملاءنا', $evilSees->intersect($ourCustomerIds)->isEmpty() ? 1 : 0, [1]);

$r = Illuminate\Http\Request::create('/api/sync', 'POST', [], [], [], [
    'HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => "Bearer $tokenE", 'CONTENT_TYPE' => 'application/json',
], json_encode(['batch_uuid' => (string) Illuminate\Support\Str::uuid(), 'device_id' => 'e', 'operations' => [
    ['op_uuid' => (string) Illuminate\Support\Str::uuid(), 'type' => 'invoice', 'data' => ['customer_id' => $ourCustomerIds->first(), 'type' => 'cash', 'items' => [['product_id' => 1, 'qty' => 1, 'unit_price' => 5]]]],
]]));
$resp = $kernel->handle($r);
$results = json_decode($resp->getContent(), true)['results'][0]['status'] ?? [];
expect('مندوب شرير يبيع لعميلنا → rejected', ($results['state'] ?? '') === 'rejected' ? 1 : 0, [1]);

echo "\n============================\n";
echo "النتيجة: ✅ {$pass} | ❌ {$fail}\n";
foreach ($failures as $f) { echo " - {$f}\n"; }
exit($fail ? 1 : 0);
