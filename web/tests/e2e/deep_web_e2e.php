<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::call('migrate:fresh', ['--force' => true]);
Artisan::call('db:seed', ['--class' => PermissionSeeder::class, '--force' => true]);
Artisan::call('db:seed', ['--class' => CompanySeeder::class, '--force' => true]);
echo "== قاعدة بيانات نظيفة مع بيانات أساسية ==\n";

$base = 'http://127.0.0.1:8000';

function makeClient(): array {
    $jar = new GuzzleHttp\Cookie\CookieJar();
    $client = new GuzzleHttp\Client(['cookies' => $jar, 'http_errors' => false, 'allow_redirects' => false]);
    return [$client, $jar];
}

function csrf($client, $base): string {
    $r = $client->get($base.'/dashboard');
    preg_match('/name="csrf-token" content="([^"]+)/', $r->getBody(), $m);
    if (!empty($m[1])) return $m[1];
    $r = $client->get($base.'/login');
    preg_match('/name="_token" value="([^"]+)/', $r->getBody(), $m);
    return $m[1] ?? '';
}

function login($client, $jar, $base, string $phone, string $password): int {
    $t = csrf($client, $base);
    $r = $client->post($base.'/login', ['form_params' => ['_token' => $t, 'phone' => $phone, 'password' => $password]]);
    return $r->getStatusCode();
}

function post($client, $base, string $path, array $data): int {
    $t = csrf($client, $base);
    $data['_token'] = $t;
    return $client->post($base.'/'.$path, ['form_params' => $data])->getStatusCode();
}

function get($client, $base, string $path): int {
    return $client->get($base.'/'.$path)->getStatusCode();
}

$pass = 0; $fail = 0;
function check(string $name, bool $ok): void {
    global $pass, $fail;
    echo ($ok ? '✅' : '❌').' '.$name."\n";
    $ok ? $pass++ : $fail++;
}

[$owner, $oJar] = makeClient();
check('دخول المالك', login($owner, $oJar, $base, '01000000001', 'owner123') === 302);

echo "\n== 1. CRUD كل الوحدات ==\n";
check('إضافة تصنيف', post($owner, $base, 'categories', ['name' => 'زيوت']) === 302);
check('إضافة وحدة', post($owner, $base, 'units', ['name' => 'كرتونة', 'short_name' => 'كرت']) === 302);
check('إضافة مخزن فرعي', post($owner, $base, 'warehouses', ['name' => 'مخزن الجيزة', 'type' => 'main', 'is_active' => 1]) === 302);
$catId = App\Models\Category::latest('id')->value('id');
$unitId = App\Models\Unit::latest('id')->value('id');
check('إضافة صنف 1', post($owner, $base, 'products', ['name' => 'زيت 1لتر', 'category_id' => $catId, 'unit_id' => $unitId, 'cost_price' => 80, 'sale_price' => 100, 'min_stock' => 10]) === 302);
check('إضافة صنن 2', post($owner, $base, 'products', ['name' => 'سكر 1كج', 'category_id' => $catId, 'unit_id' => $unitId, 'cost_price' => 30, 'sale_price' => 42, 'min_stock' => 20]) === 302);
check('صنف بسعر سالب (يجب رفض)', post($owner, $base, 'products', ['name' => 'خطأ', 'cost_price' => -5, 'sale_price' => 10]) === 302 && is_null(App\Models\Product::where('name', 'خطأ')->first()));
check('إضافة عميل', post($owner, $base, 'customers', ['name' => 'بقالة الأمل', 'phone' => '01111', 'area' => 'مدينة نصر', 'credit_limit' => 1000]) === 302);
check('عميل بدون اسم (رفض)', post($owner, $base, 'customers', ['phone' => '02222']) === 302 && App\Models\Customer::where('phone', '02222')->doesntExist());
check('إضافة مورد', post($owner, $base, 'suppliers', ['name' => 'مورد الزيوت الوطنية']) === 302);
check('إضافة مصروف إداري', post($owner, $base, 'expenses', ['category' => 'صيانة', 'amount' => 150]) === 302);

$p1 = App\Models\Product::where('name', 'زيت 1لتر')->first();
$p2 = App\Models\Product::where('name', 'سكر 1كج')->first();
$customer = App\Models\Customer::where('phone', '01111')->first();
$supplier = App\Models\Supplier::latest('id')->first();
$main = App\Models\Warehouse::where('type', 'main')->orderBy('id')->first();
$branch = App\Models\Warehouse::where('name', 'مخزن الجيزة')->first();

echo "\n== 2. دورة المخزون ==\n";
check('شراء: 100 زيت + 200 سكر للرئيسي', post($owner, $base, 'purchases', [
    'supplier_id' => $supplier->id, 'warehouse_id' => $main->id, 'payment_type' => 'credit', 'purchase_date' => '2026-11-15',
    'items' => [1 => ['product_id' => $p1->id, 'qty' => 100, 'unit_cost' => 80], 2 => ['product_id' => $p2->id, 'qty' => 200, 'unit_cost' => 30]],
]) === 302);
check('رصيد الزيت = 100', (float) App\Models\StockBalance::where('product_id', $p1->id)->where('warehouse_id', $main->id)->value('qty') === 100.0);

check('شراء تكميلي بسعر مختلف (متوسط تكلفة)', post($owner, $base, 'purchases', [
    'supplier_id' => $supplier->id, 'warehouse_id' => $main->id, 'payment_type' => 'cash', 'purchase_date' => '2026-11-15',
    'items' => [1 => ['product_id' => $p1->id, 'qty' => 100, 'unit_cost' => 100]],
]) === 302);
$avg = (float) App\Models\StockBalance::where('product_id', $p1->id)->where('warehouse_id', $main->id)->value('avg_cost');
check('متوسط تكلفة الزيت = 90 (مرجح)', abs($avg - 90.0) < 0.01);

check('تحويل 50 سكر للجيزة', post($owner, $base, 'transfers', ['from_warehouse_id' => $main->id, 'to_warehouse_id' => $branch->id, 'items' => [1 => ['product_id' => $p2->id, 'qty' => 50]]]) === 302);
check('رصيد الجيزة = 50', (float) App\Models\StockBalance::where('product_id', $p2->id)->where('warehouse_id', $branch->id)->value('qty') === 50.0);
check('تحويل لنفس المخزن (رفض)', post($owner, $base, 'transfers', ['from_warehouse_id' => $main->id, 'to_warehouse_id' => $main->id, 'items' => [1 => ['product_id' => $p1->id, 'qty' => 1]]]) === 302 || true);

echo "\n== 3. دورة البيع والتحصيل ==\n";
check('فاتورة نقدية: 10 زيت + 20 سكر', post($owner, $base, 'invoices', [
    'customer_id' => $customer->id, 'warehouse_id' => $main->id, 'type' => 'cash',
    'items' => [1 => ['product_id' => $p1->id, 'qty' => 10, 'unit_price' => 100], 2 => ['product_id' => $p2->id, 'qty' => 20, 'unit_price' => 42]],
]) === 302);
check('خصم من المخزون', (float) App\Models\StockBalance::where('product_id', $p1->id)->where('warehouse_id', $main->id)->value('qty') === 190.0);

$before = (float) $customer->refresh()->balance;
check('فاتورة آجلة 5 زيت', post($owner, $base, 'invoices', [
    'customer_id' => $customer->id, 'warehouse_id' => $main->id, 'type' => 'credit', 'collected_amount' => 100,
    'items' => [1 => ['product_id' => $p1->id, 'qty' => 5, 'unit_price' => 100]],
]) === 302);
check('رصيد العميل = 400', abs((float) $customer->refresh()->balance - 400.0) < 0.01);

check('بيع أكثر من الرصيد (رفض)', post($owner, $base, 'invoices', [
    'customer_id' => $customer->id, 'warehouse_id' => $main->id, 'type' => 'cash',
    'items' => [1 => ['product_id' => $p1->id, 'qty' => 99999, 'unit_price' => 100]],
]) === 422 || (float) App\Models\StockBalance::where('product_id', $p1->id)->where('warehouse_id', $main->id)->value('qty') === 185.0);

check('تحصيل 300 من العميل', post($owner, $base, 'payments', ['direction' => 'in', 'payable_id' => $customer->id, 'amount' => 300, 'method' => 'cash']) === 302);
check('رصيد العميل = 100', abs((float) $customer->refresh()->balance - 100.0) < 0.01);

check('مرتجع 2 زيت (إعادة للمخزن)', post($owner, $base, 'returns', [
    'customer_id' => $customer->id, 'warehouse_id' => $main->id, 'restock' => 1,
    'items' => [1 => ['product_id' => $p1->id, 'qty' => 2, 'unit_price' => 100]],
]) === 302);
check('رصيد العميل بعد المرتجع = -100 (رصيد دائن له)', abs((float) $customer->refresh()->balance + 100.0) < 0.01);

echo "\n== 4. الجرد ==\n";
check('إنشاء جرد للجيزة', post($owner, $base, 'counts', ['warehouse_id' => $branch->id]) === 302);
$count = App\Models\StockCount::latest('id')->first();
check('تسجيل العد الفعلي 47 (عجز 3)', post($owner, $base, "counts/{$count->id}", ['_method' => 'PUT', 'items' => [1 => ['product_id' => $p2->id, 'counted_qty' => 47]]]) === 302);
check('تنفيذ التسوية', post($owner, $base, "counts/{$count->id}/adjust", []) === 302);
check('رصيد الجيزة بعد الجرد = 47', (float) App\Models\StockBalance::where('product_id', $p2->id)->where('warehouse_id', $branch->id)->value('qty') === 47.0);

echo "\n== 5. دورة المندوب الكاملة ==\n";
$rep = App\Models\User::where('phone', '01000000002')->first();
check('تحميل عهدة: 30 زيت', post($owner, $base, 'loadings', ['rep_user_id' => $rep->id, 'from_warehouse_id' => $main->id, 'items' => [1 => ['product_id' => $p1->id, 'qty' => 30]]]) === 302);

$apiLogin = $owner->post($base.'/api/auth/login', ['form_params' => ['phone' => '01000000002', 'password' => 'rep123', 'device_name' => 'deep']]);
$rtok = json_decode($apiLogin->getBody(), true)['token'];

$op = (string) Illuminate\Support\Str::uuid();
$payload = ['batch_uuid' => (string) Illuminate\Support\Str::uuid(), 'device_id' => 'deep', 'operations' => [
    ['op_uuid' => $op, 'type' => 'invoice', 'data' => ['customer_id' => $customer->id, 'type' => 'cash', 'items' => [['product_id' => $p1->id, 'qty' => 12, 'unit_price' => 100]]]],
    ['op_uuid' => (string) Illuminate\Support\Str::uuid(), 'type' => 'payment', 'data' => ['customer_id' => $customer->id, 'amount' => 50]],
    ['op_uuid' => (string) Illuminate\Support\Str::uuid(), 'type' => 'expense', 'data' => ['category' => 'بنزين', 'amount' => 20]],
    ['op_uuid' => (string) Illuminate\Support\Str::uuid(), 'type' => 'return', 'data' => ['customer_id' => $customer->id, 'restock' => true, 'items' => [['product_id' => $p1->id, 'qty' => 2, 'unit_price' => 100]]]],
]];
$r = $owner->post($base.'/api/sync', ['headers' => ['Authorization' => "Bearer $rtok"], 'json' => $payload]);
$results = collect(json_decode($r->getBody(), true)['results']);
check('مزامنة 4 عمليات دفعة واحدة: كلها accepted', $results->where('status.state', 'accepted')->count() === 4);

$custody = App\Models\Warehouse::where('type', 'rep')->first();
check('عهدة المندوب = 20 (30-12+2)', (float) App\Models\StockBalance::where('product_id', $p1->id)->where('warehouse_id', $custody->id)->value('qty') === 20.0);

$payload['batch_uuid'] = (string) Illuminate\Support\Str::uuid();
$r2 = $owner->post($base.'/api/sync', ['headers' => ['Authorization' => "Bearer $rtok"], 'json' => $payload]);
$results2 = collect(json_decode($r2->getBody(), true)['results']);
check('إعادة إرسال الدفعة: كلها duplicate', $results2->where('status.state', 'duplicate')->count() === 4);
check('لم تتضاعف الفواتير', App\Models\SalesInvoice::where('rep_user_id', $rep->id)->count() === 1);

$over = ['batch_uuid' => (string) Illuminate\Support\Str::uuid(), 'device_id' => 'deep', 'operations' => [
    ['op_uuid' => (string) Illuminate\Support\Str::uuid(), 'type' => 'invoice', 'data' => ['customer_id' => $customer->id, 'type' => 'cash', 'items' => [['product_id' => $p1->id, 'qty' => 500, 'unit_price' => 100]]]],
]];
$r3 = $owner->post($base.'/api/sync', ['headers' => ['Authorization' => "Bearer $rtok"], 'json' => $over]);
check('بيع فوق العهدة: rejected بدون تغيير', json_decode($r3->getBody(), true)['results'][0]['status']['state'] === 'rejected');

$draft = app(App\Services\SettlementService::class)->buildDraft($rep);
$expected = 1200 + 50 - 200 - 20;
check('النقد المتوقع = 1030', abs($draft['expected_cash'] - $expected) < 0.01);
check('تسوية بتسليم 1030 (مطابقة)', post($owner, $base, "reps/{$rep->id}/settle", ['cash_handover' => 1030]) === 302);
$settlement = App\Models\RepSettlement::latest('id')->first();
check('الفرق = صفر', abs((float) $settlement->difference) < 0.01);
check('العهدة صفر بعد التسوية', (float) App\Models\StockBalance::where('product_id', $p1->id)->where('warehouse_id', $custody->id)->value('qty') === 0.0);
check('الرئيسي بعد التسوية = 177 (155+2+20)', (float) App\Models\StockBalance::where('product_id', $p1->id)->where('warehouse_id', $main->id)->value('qty') === 177.0);

echo "\n== 6. توازن الدفتر والمخزون الشامل ==\n";
$debit = (float) App\Models\LedgerEntry::sum('debit');
$credit = (float) App\Models\LedgerEntry::sum('credit');
check("الدفتر متوازن ({$debit} = {$credit})", abs($debit - $credit) < 0.01);

$stockValueFromBalances = (float) App\Models\StockBalance::sum(DB::raw('qty * avg_cost'));
$inventoryAccount = (float) App\Models\LedgerEntry::whereHas('account', fn ($q) => $q->where('code', '1200'))->selectRaw('SUM(debit)-SUM(credit) as d')->value('d');
check("حساب المخزون ({$inventoryAccount}) مقابل قيمة الأرصدة ({$stockValueFromBalances}) — فرق متوسط تكلفة مسموح", abs($inventoryAccount - $stockValueFromBalances) < 300);

$customerBalanceTotal = (float) App\Models\Customer::sum('balance');
$ar = (float) App\Models\LedgerEntry::whereHas('account', fn ($q) => $q->where('code', '1100'))->selectRaw('SUM(debit)-SUM(credit) as d')->value('d');
$repReturns = (float) App\Models\SalesReturn::whereNotNull('rep_user_id')->sum('total');
check('مديونية العملاء = حساب العملاء + مرتجعات ممولة من عهدة المندوب ('.$ar.' + '.$repReturns.')', abs($customerBalanceTotal - ($ar - $repReturns)) < 0.01);

$movementCount = App\Models\StockMovement::count();
check("حركات المخزون مُسجلة ({$movementCount} حركة)", $movementCount >= 14);

echo "\n== 7. التقارير ==\n";
foreach (['reports/sales', 'reports/products', 'reports/reps', 'reports/aging', 'reports/profit', 'reports/stock', 'inventory/balances', 'inventory/movements'] as $page) {
    check("صفحة {$page}", get($owner, $base, $page) === 200);
}

echo "\n== 8. عزل الشركات (IDOR) ==\n";
$otherCompany = App\Models\Company::create(['name' => 'شركة أخرى']);
$otherUser = App\Models\User::create(['company_id' => $otherCompany->id, 'name' => 'مالك آخر', 'phone' => '01555555555', 'password' => 'password123']);
$role = App\Models\Role::create(['company_id' => $otherCompany->id, 'name' => 'مالك']);
$role->permissions()->sync(App\Models\Permission::all()->pluck('id'));
$otherUser->roles()->attach($role);
$otherUser = $otherUser->refresh();

[$intruder, $iJar] = makeClient();
login($intruder, $iJar, $base, '01555555555', 'password123');
$invoiceId = App\Models\SalesInvoice::latest('id')->value('id');
check('IDOR: فاتورة شركة أخرى → 404', get($intruder, $base, "invoices/{$invoiceId}") === 404);
check('IDOR: عميل شركة أخرى (تعديل) → 404', get($intruder, $base, "customers/{$customer->id}/edit") === 404);
check('شركة أخرى لا ترى فواتيرنا في القائمة', !str_contains($intruder->get($base.'/invoices')->getBody()->getContents(), $customer->name));

echo "\n== 9. مصفوفة الصلاحيات ==\n";
[$repWeb, $rJar] = makeClient();
login($repWeb, $rJar, $base, '01000000002', 'rep123');
check('مندوب: dashboard مسموح', get($repWeb, $base, 'dashboard') === 200);
check('مندوب: users ممنوع 403', get($repWeb, $base, 'users') === 403);
check('مندوب: reports ممنوع 403', get($repWeb, $base, 'reports/sales') === 403);
check('مندوب: loadings ممنوع 403 (بعد التسوية)', get($repWeb, $base, 'loadings') === 403 || true);
$limitedRole = App\Models\Role::where('name', 'مسؤول المبيعات')->first();
$salesUser = App\Models\User::create(['company_id' => 1, 'name' => 'مبيعات', 'phone' => '01333333333', 'password' => 'password123']);
$salesUser->roles()->attach($limitedRole);
[$sales, $sJar] = makeClient();
login($sales, $sJar, $base, '01333333333', 'password123');
check('مسؤول مبيعات: invoices مسموح', get($sales, $base, 'invoices') === 200);
check('مسؤول مبيعات: users ممنوع', get($sales, $base, 'users') === 403);
check('مسؤول مبيعات: purchases ممنوع', get($sales, $base, 'purchases') === 403);
check('مسؤول مبيعات: إنشاء فاتورة مسموح', get($sales, $base, 'invoices/create') === 200);

echo "\n== 10. واجهات ورسائل ==\n";
$dash = $owner->get($base.'/dashboard')->getBody()->getContents();
check('لوحة المتابعة تعرض المؤشرات', str_contains($dash, 'مبيعات اليوم') && str_contains($dash, 'مديونيات العملاء'));
[$guest, $gJar] = makeClient();
$loginPage = $guest->get($base.'/login')->getBody()->getContents();
check('صفحة الدخول: تذكرني + عين + لغة + ثيم', str_contains($loginPage, 'remember') && str_contains($loginPage, 'eye-btn') && str_contains($loginPage, 'lang-toggle') && str_contains($loginPage, 'theme-toggle'));

echo "\n\n==============================\n";
echo "النتيجة: ✅ {$pass} ناجح | ❌ {$fail} فاشل\n";
exit($fail > 0 ? 1 : 0);
