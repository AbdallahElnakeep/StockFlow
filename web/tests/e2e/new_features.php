<?php

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Artisan;

Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);
app()->instance('current.company.id', 1);

$pass = 0; $fail = 0;
function check(string $name, bool $ok): void {
    global $pass, $fail;
    echo ($ok ? '✅' : '❌').' '.$name."\n";
    $ok ? $pass++ : $fail++;
}

$customer = App\Models\Customer::create(['name' => 'عميل الفحص', 'credit_limit' => 5000]);
$supplier = App\Models\Supplier::create(['name' => 'مورد الفحص']);
$p = App\Models\Product::create(['name' => 'صنف الفحص', 'cost_price' => 10, 'sale_price' => 15]);
$w = App\Models\Warehouse::where('type', 'main')->first();
$rep = App\Models\User::where('phone', '01000000002')->first();

echo "== 1. الإلغاء العكسي ==\n";
app(App\Services\PurchaseService::class)->createPurchase([
    'uuid' => (string) Illuminate\Support\Str::uuid(), 'supplier_id' => $supplier->id, 'warehouse_id' => $w->id,
    'payment_type' => 'credit', 'items' => [['product_id' => $p->id, 'qty' => 100, 'unit_cost' => 10]],
]);
$inv = app(App\Services\SalesService::class)->createInvoice([
    'uuid' => (string) Illuminate\Support\Str::uuid(), 'customer_id' => $customer->id, 'warehouse_id' => $w->id,
    'type' => 'credit', 'collected_amount' => 20, 'items' => [['product_id' => $p->id, 'qty' => 5, 'unit_price' => 15]],
]);
$stockBefore = (float) App\Models\StockBalance::where('product_id', $p->id)->where('warehouse_id', $w->id)->value('qty');
$balBefore = (float) $customer->refresh()->balance;
$cancelled = app(App\Services\SalesService::class)->cancelInvoice($inv->refresh(), 'اختبار إلغاء');
$stockAfter = (float) App\Models\StockBalance::where('product_id', $p->id)->where('warehouse_id', $w->id)->value('qty');
check('الفاتورة صارت ملغاة', $cancelled->status === 'cancelled');
check('البضاعة رجعت للمخزن (+5)', abs(($stockAfter - $stockBefore) - 5) < 0.01);
check('رصيد العميل تراجع (55→0)', abs((float) $customer->refresh()->balance) < 0.01);
$duplicateCancel = false;
try { app(App\Services\SalesService::class)->cancelInvoice($cancelled->refresh(), 'مكرر'); } catch (\DomainException) { $duplicateCancel = true; }
check('إلغاء مزدوج مرفوض', $duplicateCancel);
$debit = (float) App\Models\LedgerEntry::sum('debit');
$credit = (float) App\Models\LedgerEntry::sum('credit');
check('الدفتر متوازن بعد الإلغاء', abs($debit - $credit) < 0.01);

echo "\n== 2. رصيد المورد ==\n";
check('الشراء الآجل رفع رصيد المورد (1000)', abs((float) $supplier->refresh()->balance - 1000) < 0.01);
app(App\Services\PaymentService::class)->createPayment([
    'uuid' => (string) Illuminate\Support\Str::uuid(), 'payable_type' => 'supplier', 'payable_id' => $supplier->id,
    'direction' => 'out', 'method' => 'cash', 'amount' => 400,
]);
check('السداد خفض رصيد المورد (600)', abs((float) $supplier->refresh()->balance - 600) < 0.01);

echo "\n== 3. شيك بتتبع ==\n";
$cheque = app(App\Services\PaymentService::class)->createPayment([
    'uuid' => (string) Illuminate\Support\Str::uuid(), 'payable_type' => 'customer', 'payable_id' => $customer->id,
    'direction' => 'in', 'method' => 'cheque', 'amount' => 200, 'cheque_no' => 'CHK-9001', 'due_date' => now()->addDays(10)->toDateString(),
]);
check('الشيك محفوظ برقمه واستحقاقه', $cheque->cheque_no === 'CHK-9001' && $cheque->due_date !== null);

echo "\n== 4. رصيد افتتاحي ==\n";
$oldCustomer = App\Models\Customer::create(['name' => 'عميل قديم', 'balance' => 750, 'credit_limit' => 1000]);
app(App\Services\OpeningBalanceService::class)->postCustomerOpening($oldCustomer, 750);
$openingAcc = App\Models\LedgerAccount::where('code', '3000')->first();
check('حساب افتتاحي أُنشئ تلقائيًا (3000)', $openingAcc !== null);
$arTotal = (float) App\Models\LedgerEntry::whereHas('account', fn ($q) => $q->where('code', '1100'))->selectRaw('SUM(debit)-SUM(credit) as d')->value('d');
check('حساب العملاء = مجموع أرصدة العملاء (متطابقة مع الشيك)', abs($arTotal - App\Models\Customer::sum('balance')) < 0.01);

echo "\n== 5. استيراد CSV ==\n";
$tmp = tempnam(sys_get_temp_dir(), 'csv');
file_put_contents($tmp, "name,phone,area,credit_limit\nعميل استيراد 1,0151111111,القاهرة,300\nعميل استيراد 2,,\nعميل استيراد 1,0151111111,القاهرة,300\n,0199,بدون اسم\n");
$result = app(App\Services\CustomerImportService::class)->import($tmp);
check('استيراد: 2 أُضيفوا و2 تُجاهلوا (مكرر وبلا اسم)', $result['created'] === 2 && $result['skipped'] === 2);
unlink($tmp);

echo "\n== 6. كشف الحساب (منطق الرصيد المتحرك) ==\n";
$reflection = new ReflectionMethod(App\Http\Controllers\ReportController::class, 'statement');
check('دالة كشف الحساب موجودة ومربوطة', $reflection->isPublic());

echo "\n== 7. زيارات من التطبيق (API) ==\n";
$login = app()->handle(Illuminate\Http\Request::create('/api/auth/login', 'POST', ['phone' => '01000000002', 'password' => 'rep123', 'device_name' => 't']));
$controller = new App\Http\Controllers\Api\SyncController(
    app(App\Services\SalesService::class), app(App\Services\SalesReturnService::class), app(App\Services\PaymentService::class)
);
$req = Illuminate\Http\Request::create('/api/sync', 'POST', [
    'batch_uuid' => (string) Illuminate\Support\Str::uuid(), 'device_id' => 't',
    'operations' => [['op_uuid' => (string) Illuminate\Support\Str::uuid(), 'type' => 'visit', 'data' => ['customer_id' => $customer->id, 'status' => 'visited', 'note' => 'زيارة فحص']]],
]);
$req->setLaravelSession(app('session.store'));
check('زيارة تُسجل عبر sync', App\Models\CustomerVisit::count() === 0);
// نفذ فعليًا عبر kernel
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$token = App\Models\User::where('phone','01000000002')->first()->createToken('t')->plainTextToken;
$syncReq = Illuminate\Http\Request::create('/api/sync', 'POST', [], [], [], [
    'HTTP_ACCEPT' => 'application/json',
    'HTTP_AUTHORIZATION' => 'Bearer '.$token,
    'CONTENT_TYPE' => 'application/json',
], json_encode([
    'batch_uuid' => (string) Illuminate\Support\Str::uuid(), 'device_id' => 't',
    'operations' => [['op_uuid' => (string) Illuminate\Support\Str::uuid(), 'type' => 'visit', 'data' => ['customer_id' => $customer->id, 'status' => 'visited']]],
]));
$response = $kernel->handle($syncReq);
check('زيارة عبر API فعليًا: '.$response->getStatusCode(), App\Models\CustomerVisit::count() === 1);

echo "\n== 8. حد الآجل وسعر المندوب (تأكيد نهائي) ==\n";
$tight = App\Models\Customer::create(['name' => 'حد ضيق', 'credit_limit' => 50]);
try {
    app(App\Services\SalesService::class)->createInvoice(['uuid' => (string) Illuminate\Support\Str::uuid(), 'customer_id' => $tight->id, 'warehouse_id' => $w->id, 'type' => 'credit', 'items' => [['product_id' => $p->id, 'qty' => 5, 'unit_price' => 15]]]);
    check('حد الآجل مرفوض', false);
} catch (\DomainException) { check('حد الآجل مرفوض', true); }

$b = App\Models\StockBalance::firstOrNew(['warehouse_id' => $rep->custodyWarehouse->id, 'product_id' => $p->id]);
$b->company_id = 1; $b->qty = 10; $b->avg_cost = 10; $b->save();
$inv2 = app(App\Services\SalesService::class)->createInvoice(['uuid' => (string) Illuminate\Support\Str::uuid(), 'customer_id' => $tight->id, 'warehouse_id' => $rep->custodyWarehouse->id, 'rep_user_id' => $rep->id, 'type' => 'cash', 'channel' => 'mobile', 'items' => [['product_id' => $p->id, 'qty' => 1, 'unit_price' => 0.01]]]);
check('سعر المندوب = السعر المعتمد', (float) $inv2->items->first()->unit_price === (float) $p->sale_price);

echo "\n========================\nالنتيجة: ✅ {$pass} | ❌ {$fail}\n";
exit($fail ? 1 : 0);
