<?php
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Artisan;

Artisan::call('migrate:fresh', ['--force' => true]);
foreach (['PermissionSeeder', 'CompanySeeder', 'DemoDataSeeder', 'TestAccountsSeeder'] as $s) {
    Artisan::call('db:seed', ['--class' => $s, '--force' => true]);
}
echo "== بيئة اختبار نظيفة ==\n";

$base = 'http://127.0.0.1:8000';
$pass = 0; $fail = 0; $failures = [];
function check(string $name, bool $ok, string $detail = ''): void {
    global $pass, $fail, $failures;
    if ($ok) { $pass++; echo "PASS  $name\n"; }
    else { $fail++; $failures[] = $name.($detail ? " :: $detail" : ''); echo "FAIL  $name  $detail\n"; }
}

function webLogin(string $base, string $phone, string $pass): GuzzleHttp\Client {
    $jar = new GuzzleHttp\Cookie\CookieJar();
    $c = new GuzzleHttp\Client(['cookies' => $jar, 'http_errors' => false, 'allow_redirects' => false]);
    $r = $c->get("$base/login");
    preg_match('/name="_token" value="([^"]+)"/', (string)$r->getBody(), $m);
    $r = $c->post("$base/login", ['form_params' => ['_token' => $m[1], 'phone' => $phone, 'password' => $pass]]);
    if ($r->getStatusCode() !== 302) throw new Exception("web login failed: $phone");
    return $c;
}
function webCsrf(GuzzleHttp\Client $c, string $base): string {
    $r = $c->get("$base/dashboard");
    preg_match('/name="csrf-token" content="([^"]+)"/', (string)$r->getBody(), $m);
    return $m[1] ?? '';
}
function apiLogin(string $base, string $phone, string $passw): array {
    $c = new GuzzleHttp\Client(['http_errors' => false]);
    $r = $c->post("$base/api/auth/login", ['json' => ['phone' => $phone, 'password' => $passw, 'device_name' => 'qa-test']]);
    return [$r->getStatusCode(), json_decode((string)$r->getBody(), true)];
}

$web = webLogin($base, '01000000001', 'owner123');
$csrf = webCsrf($web, $base);

echo "\n== المرحلة 1: الويب هو مصدر الإضافة ==\n";
$r = $web->post("$base/customers", ['form_params' => ['_token' => $csrf, 'name' => 'عميل التكامل QA', 'phone' => '01099999999', 'area' => 'التكامل', 'credit_limit' => 500]]);
$customer = App\Models\Customer::where('name', 'عميل التكامل QA')->first();
check('ويب: إضافة عميل جديدة', $r->getStatusCode() === 302 && $customer !== null);
check('ويب: العميل يظهر في صفحة القائمة', str_contains((string)$web->get("$base/customers")->getBody(), 'عميل التكامل QA'));

[$code, $mob] = apiLogin($base, '01000000002', 'rep123');
$token = $mob['token'] ?? '';
$ac = new GuzzleHttp\Client(['http_errors' => false]);
check('موبايل: تسجيل دخول المندوب API', $code === 200 && !empty($token));
$r = $ac->get("$base/api/bootstrap", ['headers' => ['Authorization' => "Bearer $token"]]);
$boot = json_decode((string)$r->getBody(), true);
$bootCustomer = collect($boot['customers'] ?? [])->firstWhere('name', 'عميل التكامل QA');
check('تزامن: عميل أُضيف من الويب يظهر في bootstrap الموبايل', $r->getStatusCode() === 200 && $bootCustomer !== null);

echo "\n== المرحلة 2: الموبايل ينشئ فاتورة وينعكس على الويب ==\n";
$rep = App\Models\User::where('phone', '01000000002')->first();
$custodyId = $rep->custodyWarehouse->id;
$stockRow = App\Models\StockBalance::where('warehouse_id', $custodyId)->where('qty', '>', 5)->first();
$prodId = $stockRow->product_id; $before = (float)$stockRow->qty;
$salePrice = (float)$stockRow->product->sale_price;
$batch = (string) Illuminate\Support\Str::uuid();
$op = (string) Illuminate\Support\Str::uuid();
$invPayload = ['batch_uuid' => $batch, 'device_id' => 'qa-device', 'operations' => [[
    'op_uuid' => $op, 'type' => 'invoice',
    'data' => ['customer_id' => $customer->id, 'type' => 'cash', 'items' => [['product_id' => $prodId, 'qty' => 2, 'unit_price' => 999]]],
]]];
$r = $ac->post("$base/api/sync", ['headers' => ['Authorization' => "Bearer $token"], 'json' => $invPayload]);
$syncBody = json_decode((string)$r->getBody(), true);
$invState = $syncBody['results'][0]['status']['state'] ?? 'none';
$invoice = App\Models\SalesInvoice::where('uuid', $op)->first();
check('موبايل: فاتورة عبر sync قُبلت', $r->getStatusCode() === 200 && $invState === 'accepted' && $invoice !== null);
check('أمان الأسعار: السيرفر تجاهل 999 واستخدم سعر البيع', $invoice && abs((float)$invoice->items()->first()->unit_price - $salePrice) < 0.001);
$invHtml = (string)$web->get("$base/invoices")->getBody();
check('ويب: الفاتورة تظهر في صفحة الفواتير', $invoice && str_contains($invHtml, $invoice->number));
$after = (float) App\Models\StockBalance::where('warehouse_id', $custodyId)->where('product_id', $prodId)->value('qty');
check('تزامن: مخزون العهدة نقص في قاعدة البيانات', abs($after - ($before - 2)) < 0.001, "before=$before after=$after");
$r = $ac->get("$base/api/bootstrap", ['headers' => ['Authorization' => "Bearer $token"]]);
$boot2 = json_decode((string)$r->getBody(), true);
$mobQty = collect($boot2['custody']['balances'] ?? [])->firstWhere('product_id', $prodId)['qty'] ?? null;
check('تزامن: رصيد العهدة في الموبايل = رصيد الويب', $mobQty !== null && abs((float)$mobQty - $after) < 0.001);
$ledgerBal = App\Models\LedgerEntry::where('ref_type', 'sales_invoice')->where('ref_id', $invoice->id)
    ->selectRaw('COALESCE(SUM(debit),0) as d, COALESCE(SUM(credit),0) as c')->first();
check('محاسبة: القيد مزدوج متوازن للفاتورة', abs($ledgerBal->d - $ledgerBal->c) < 0.001 && $ledgerBal->d > 0);

echo "\n== المرحلة 3: Idempotency وإعادة الإرسال ==\n";
$r = $ac->post("$base/api/sync", ['headers' => ['Authorization' => "Bearer $token"], 'json' => $invPayload]);
$replay = json_decode((string)$r->getBody(), true);
check('ضغط: إعادة نفس الدفعة batch_uuid لا تنشئ فاتورة ثانية', ($replay['results'][0]['status']['state'] ?? '') === 'accepted'
    && App\Models\SalesInvoice::where('customer_id', $customer->id)->count() === 1);
$dupPayload = ['batch_uuid' => (string) Illuminate\Support\Str::uuid(), 'device_id' => 'qa-device', 'operations' => [[
    'op_uuid' => $op, 'type' => 'invoice',
    'data' => ['customer_id' => $customer->id, 'type' => 'cash', 'items' => [['product_id' => $prodId, 'qty' => 2, 'unit_price' => 1]]],
]]];
$r = $ac->post("$base/api/sync", ['headers' => ['Authorization' => "Bearer $token"], 'json' => $dupPayload]);
$dupState = json_decode((string)$r->getBody(), true)['results'][0]['status']['state'] ?? 'none';
check('ضغط: نفس op_uuid في دفعة جديدة = duplicate بدون تكرار', $dupState === 'duplicate' && App\Models\SalesInvoice::where('customer_id', $customer->id)->count() === 1);

echo "\n== المرحلة 4: الموبايل يحصّل والرصيد يتزامن ==\n";
$balBefore = (float)$customer->fresh()->balance;
$payOp = (string) Illuminate\Support\Str::uuid();
$r = $ac->post("$base/api/sync", ['headers' => ['Authorization' => "Bearer $token"], 'json' => ['batch_uuid' => (string) Illuminate\Support\Str::uuid(), 'device_id' => 'qa-device', 'operations' => [[
    'op_uuid' => $payOp, 'type' => 'payment', 'data' => ['customer_id' => $customer->id, 'amount' => 50, 'note' => 'تحصيل QA'],
]]]]);
$payState = json_decode((string)$r->getBody(), true)['results'][0]['status']['state'] ?? 'none';
check('موبايل: تحصيل نقدى قُبل', $payState === 'accepted');
$balAfter = (float)$customer->fresh()->balance;
check('تزامن: التحصيل خفض رصيد العميل (نقدي لا يغيّر الرصيد)', abs($balAfter - ($balBefore - 50)) < 0.01, "before=$balBefore after=$balAfter");
$pay = App\Models\Payment::where('uuid', $payOp)->first();
check('ويب: التحصيل يظهر في صفحة التحصيلات', $pay && str_contains((string)$web->get("$base/payments")->getBody(), $pay->number));

echo "\n== المرحلة 5: الإعدادات تتحكم فعليًا في قواعد العمل ==\n";
$customer->fresh()->update(['credit_limit' => 100]);
$web->put("$base/settings", ['form_params' => ['_token' => $csrf, 'enforce_credit_limit' => '1']]);
$customer->refresh();
$customer->update(['balance' => 400]);
$r = $ac->post("$base/api/sync", ['headers' => ['Authorization' => "Bearer $token"], 'json' => ['batch_uuid' => (string) Illuminate\Support\Str::uuid(), 'device_id' => 'qa-device', 'operations' => [[
    'op_uuid' => (string) Illuminate\Support\Str::uuid(), 'type' => 'invoice', 'data' => ['customer_id' => $customer->id, 'type' => 'credit', 'items' => [['product_id' => $prodId, 'qty' => 10, 'unit_price' => 1]]],
]]]]);
$creditState = json_decode((string)$r->getBody(), true)['results'][0]['status']['state'] ?? 'none';
check('قاعدة: حد الآجل مطبق على الموبايل (رفض عند التجاوز)', $creditState === 'rejected', "state=$creditState");
$web->put("$base/settings", ['form_params' => ['_token' => $csrf, 'company_name' => 'شركة الأمل للتوزيع', 'enforce_credit_limit' => '0']]);
$r = $ac->post("$base/api/sync", ['headers' => ['Authorization' => "Bearer $token"], 'json' => ['batch_uuid' => (string) Illuminate\Support\Str::uuid(), 'device_id' => 'qa-device', 'operations' => [[
    'op_uuid' => (string) Illuminate\Support\Str::uuid(), 'type' => 'invoice', 'data' => ['customer_id' => $customer->id, 'type' => 'credit', 'items' => [['product_id' => $prodId, 'qty' => 1, 'unit_price' => 1]]],
]]]]);
$afterToggle = json_decode((string)$r->getBody(), true)['results'][0]['status']['state'] ?? 'none';
check('لوحة التحكم: تعطيل فرض حد الآجل أثّر على الموبايل فورًا', $afterToggle === 'accepted', "state=$afterToggle");
$web->put("$base/settings", ['form_params' => ['_token' => $csrf, 'company_name' => 'شركة الأمل للتوزيع', 'enforce_credit_limit' => '1']]);

echo "\n== المرحلة 6: إيقاف عناصر من الويب ينعكس على الموبايل ==\n";
$product = App\Models\Product::find($prodId);
$product->update(['is_active' => false]);
$r = $ac->get("$base/api/bootstrap", ['headers' => ['Authorization' => "Bearer $token"]]);
$boot3 = json_decode((string)$r->getBody(), true);
$still = collect($boot3['products'] ?? [])->firstWhere('id', $prodId);
check('تزامن: تعطيل صنف من الويب أزاله من bootstrap الموبايل', $still === null);
$product->update(['is_active' => true]);
$customer->update(['is_active' => false]);
$r = $ac->get("$base/api/bootstrap", ['headers' => ['Authorization' => "Bearer $token"]]);
$boot4 = json_decode((string)$r->getBody(), true);
$stillC = collect($boot4['customers'] ?? [])->firstWhere('id', $customer->id);
check('تزامن: إيقاف عميل من الويب أزاله من قائمة عملاء الموبايل', $stillC === null);
$customer->update(['is_active' => true]);

echo "\n== المرحلة 7: أمن — عزل الشركات وإبطال الرموز ==\n";
$company2 = App\Models\Company::create(['name' => 'شركة اختبار 2', 'is_active' => true]);
app()->instance('current.company.id', $company2->id);
App\Models\User::create(['company_id' => $company2->id, 'name' => 'مالك2', 'phone' => '01511111111', 'password' => Illuminate\Support\Facades\Hash::make('x12345')]);
[$c2, $m2] = apiLogin($base, '01511111111', 'x12345');
$cross = ['batch_uuid' => (string) Illuminate\Support\Str::uuid(), 'device_id' => 'qa2', 'operations' => [[
    'op_uuid' => (string) Illuminate\Support\Str::uuid(), 'type' => 'invoice', 'data' => ['customer_id' => $customer->id, 'type' => 'cash', 'items' => [['product_id' => $prodId, 'qty' => 1, 'unit_price' => 1]]],
]]];
$r = $ac->post("$base/api/sync", ['headers' => ['Authorization' => 'Bearer '.($m2['token'] ?? ''), 'Accept' => 'application/json'], 'json' => $cross]);
$crossState = json_decode((string)$r->getBody(), true)['results'][0]['status']['state'] ?? 'none';
check('أمن: مستخدم شركة أخرى لا يبيع لعملاء شركتي (API)', $crossState === 'rejected' || $r->getStatusCode() === 403, "code={$r->getStatusCode()} state=$crossState");
$ac->post("$base/api/auth/logout", ['headers' => ['Authorization' => 'Bearer '.($m2['token'] ?? ''), 'Accept' => 'application/json']]);
$r = $ac->get("$base/api/bootstrap", ['headers' => ['Authorization' => 'Bearer '.($m2['token'] ?? ''), 'Accept' => 'application/json']]);
check('أمن: بعد تسجيل الخروج يُبطل رمز API (401)', $r->getStatusCode() === 401, 'code='.$r->getStatusCode());
$r = $ac->get("$base/api/bootstrap", ['headers' => ['Authorization' => 'Bearer wrong-token', 'Accept' => 'application/json']]);
check('أمن: رمز API غير صالح = 401 وليس 500', $r->getStatusCode() === 401);

echo "\n== المرحلة 8: الضغط — استنزاف المخزون ==\n";
app()->instance('current.company.id', $rep->company_id);
$mainWh = App\Models\Warehouse::where('type', 'main')->first();
app(App\Services\RepLoadingService::class)->create([
    'rep_user_id' => $rep->id,
    'from_warehouse_id' => $mainWh->id,
    'uuid' => (string) Illuminate\Support\Str::uuid(),
    'items' => [['product_id' => $prodId, 'qty' => 30, 'unit_price' => 1]],
]);
$remainRow = App\Models\StockBalance::where('warehouse_id', $custodyId)->where('product_id', $prodId)->first();
if ($remainRow) {
    $p2 = $remainRow->product_id; $remain = (float)$remainRow->qty;
    $ops = [];
    for ($i = 0; $i < (int) ceil($remain) + 1; $i++) {
        $ops[] = ['op_uuid' => (string) Illuminate\Support\Str::uuid(), 'type' => 'invoice', 'data' => ['customer_id' => $customer->id, 'type' => 'cash', 'items' => [['product_id' => $p2, 'qty' => 1, 'unit_price' => 1]]]];
    }
    $r = $ac->post("$base/api/sync", ['headers' => ['Authorization' => "Bearer $token"], 'json' => ['batch_uuid' => (string) Illuminate\Support\Str::uuid(), 'device_id' => 'qa-device', 'operations' => $ops]]);
    $res = json_decode((string)$r->getBody(), true)['results'];
    $accepted = collect($res)->filter(fn ($x) => $x['status']['state'] === 'accepted')->count();
    $rejected = collect($res)->filter(fn ($x) => $x['status']['state'] === 'rejected')->count();
    $finalQty = (float) App\Models\StockBalance::where('warehouse_id', $custodyId)->where('product_id', $p2)->value('qty');
    check('ضغط: استنزاف المخزون — لا بيع فوق الموجود', $finalQty === 0.0 && $accepted === (int) floor($remain) && $rejected >= 1, "accepted=$accepted rejected=$rejected remain=$remain final=$finalQty");
} else {
    echo "SKIP  ضغط الاستنزاف (لا يوجد رصيد كافٍ)\n";
}

echo "\n== المرحلة 9: حماية الجلسات ==\n";
$guest = new GuzzleHttp\Client(['http_errors' => false, 'allow_redirects' => false]);
check('أمن: زائر بدون جلسة يُحوّل لتسجيل الدخول', $guest->get("$base/customers")->getStatusCode() === 302);
$r = $ac->post("$base/api/sync", ['headers' => ['Accept' => 'application/json'], 'json' => $invPayload]);
check('أمن: مزامنة بدون رمز مرفوضة (وليست 500)', in_array($r->getStatusCode(), [401, 302, 422]));

echo "\n========\nTOTAL: PASS=$pass FAIL=$fail\n";
if ($failures) { echo "FAILURES:\n".implode("\n", $failures)."\n"; }
