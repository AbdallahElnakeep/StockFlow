<?php

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Artisan;

Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);
app()->instance('current.company.id', 1);

$gaps = [];
function gap(string $severity, string $title, string $evidence): void {
    global $gaps;
    $gaps[] = compact('severity', 'title', 'evidence');
    echo ($severity === 'critical' ? '🔴' : ($severity === 'major' ? '🟠' : '🟡'))." {$title}\n   ↳ {$evidence}\n";
}
function ok(string $title, string $evidence = ''): void {
    echo "✅ {$title}".($evidence ? " — {$evidence}" : '')."\n";
}

$owner = App\Models\User::where('phone', '01000000001')->first();
$rep = App\Models\User::where('phone', '01000000002')->first();
$custody = $rep->custodyWarehouse;
$main = App\Models\Warehouse::where('type', 'main')->orderBy('id')->first();
$p = App\Models\Product::first();
$customer = App\Models\Customer::first();
$customer->update(['credit_limit' => 999999]);

echo "== أ. إلغاء فاتورة مندوب بعد التسوية ==\n";
$b = App\Models\StockBalance::firstOrNew(['warehouse_id' => $custody->id, 'product_id' => $p->id]);
$b->company_id = 1; $b->qty = 50; $b->avg_cost = 10; $b->save();
$repInv = app(App\Services\SalesService::class)->createInvoice([
    'uuid' => (string) Illuminate\Support\Str::uuid(), 'customer_id' => $customer->id, 'warehouse_id' => $custody->id,
    'rep_user_id' => $rep->id, 'type' => 'cash', 'channel' => 'mobile',
    'items' => [['product_id' => $p->id, 'qty' => 10, 'unit_price' => $p->sale_price]],
], $rep);
app(App\Services\SettlementService::class)->settle($rep, 100000, ['uuid' => (string) Illuminate\Support\Str::uuid()], $owner);
try {
    app(App\Services\SalesService::class)->cancelInvoice($repInv->refresh(), 'بعد التسوية');
    $custodyQty = (float) App\Models\StockBalance::where('warehouse_id', $custody->id)->where('product_id', $p->id)->value('qty');
    if ($custodyQty > 0) {
        gap('critical', 'إلغاء فاتورة مندوب بعد التسوية يُرجع بضاعة لعهدة مغلقة', "عهدة صارت {$custodyQty} بعد الإلغاء");
    } else {
        gap('major', 'إلغاء فاتورة مُسوّاة مسموح', 'بلا مانع رغم إغلاق التسوية');
    }
} catch (\DomainException $e) {
    ok('إلغاء بعد التسوية مرفوض', $e->getMessage());
}

echo "\n== ب. تحويل مخزني مباشر إلى عهدة مندوب ==\n";
try {
    app(App\Services\TransferService::class)->create([
        'uuid' => (string) Illuminate\Support\Str::uuid(), 'from_warehouse_id' => $main->id, 'to_warehouse_id' => $custody->id,
        'items' => [['product_id' => $p->id, 'qty' => 5]],
    ], $owner);
    gap('major', 'تحويل عام إلى عهدة مندوب مسموح', 'يتجاوز دورة التحميل');
} catch (\DomainException $e) {
    ok('تحويل للعهدة مرفوض', $e->getMessage());
}

echo "\n== ج. مرتجع بأكثر من المُباع ==\n";
$c2 = App\Models\Customer::create(['name' => 'مرتجع كثير', 'credit_limit' => 999999]);
$b2 = App\Models\StockBalance::firstOrNew(['warehouse_id' => $main->id, 'product_id' => $p->id]);
$b2->company_id = 1; $b2->qty = 500; $b2->avg_cost = 10; $b2->save();
$inv3 = app(App\Services\SalesService::class)->createInvoice([
    'uuid' => (string) Illuminate\Support\Str::uuid(), 'customer_id' => $c2->id, 'warehouse_id' => $main->id, 'type' => 'cash',
    'items' => [['product_id' => $p->id, 'qty' => 5, 'unit_price' => $p->sale_price]],
], $owner);
try {
    app(App\Services\SalesReturnService::class)->createReturn([
        'uuid' => (string) Illuminate\Support\Str::uuid(), 'customer_id' => $c2->id, 'sales_invoice_id' => $inv3->id,
        'warehouse_id' => $main->id, 'restock' => true,
        'items' => [['product_id' => $p->id, 'qty' => 20, 'unit_price' => $p->sale_price]],
    ], $owner);
    gap('major', 'مرتجع مرتبط بفاتورة لا يتحقق من الكمية المُباع', 'باع 5 ورجّع 20');
} catch (\DomainException $e) {
    ok('مرتجع زائد عن المُباع مرفوض', $e->getMessage());
}

echo "\n== د. الشيك تحت التحصيل ==\n";
$balBefore = (float) $c2->refresh()->balance;
app(App\Services\PaymentService::class)->createPayment([
    'uuid' => (string) Illuminate\Support\Str::uuid(), 'payable_type' => 'customer', 'payable_id' => $c2->id,
    'direction' => 'in', 'method' => 'cheque', 'amount' => 100, 'cheque_no' => 'T-1', 'due_date' => now()->addDays(30),
], $owner);
$chequeAccount = (float) App\Models\LedgerEntry::whereHas('account', fn ($q) => $q->where('code', '1050'))->selectRaw('SUM(debit)-SUM(credit) as d')->value('d');
if ($chequeAccount >= 100) {
    ok('الشيك يُقيَّد في حساب مخصص (1050) وليس الصندوق', 'رصيد 1050 = '.$chequeAccount);
} else {
    gap('major', 'الشيك يُعامل كنقد فوري', 'لو ارتد يضيع الفرق');
}

echo "\n== هـ. سداد فوق مستحق المورد ==\n";
$sup = App\Models\Supplier::first();
try {
    app(App\Services\PaymentService::class)->createPayment([
        'uuid' => (string) Illuminate\Support\Str::uuid(), 'payable_type' => 'supplier', 'payable_id' => $sup->id,
        'direction' => 'out', 'method' => 'cash', 'amount' => 999999,
    ], $owner);
    gap('minor', 'سداد فوق مستحق المورد مسموح (دفعات مقدمة بلا عرض)', 'رصيد المورد: '.$sup->refresh()->balance);
} catch (\DomainException) { ok('سداد فوق المستحق مرفوض'); }

echo "\n== و. مرتجع مشتريات للمورد ==\n";
if (!class_exists('App\\Services\\PurchaseReturnService')) {
    gap('major', 'لا يوجد مرتجع مشتريات نهائيًا', 'البضاعة المرتجعة للمورد أو التالفة قبل البيع لا طريق لتسجيلها');
}

echo "\n== ز. جرد بعجز واسع ==\n";
try {
    $count = app(App\Services\StockCountService::class)->create(['uuid' => (string) Illuminate\Support\Str::uuid(), 'warehouse_id' => $main->id], $owner);
    app(App\Services\StockCountService::class)->setCounted($count, App\Models\StockBalance::where('warehouse_id', $main->id)->get()->map(fn ($bb) => ['product_id' => $bb->product_id, 'counted_qty' => (float) $bb->qty - 1])->all());
    app(App\Services\StockCountService::class)->adjust($count, $owner);
    ok('تسوية جرد بعجز تعمل', 'خصم وحدة من كل صنف بقيد تلقائي');
} catch (\Throwable $e) { gap('major', 'تسوية جرد فشلت', $e->getMessage()); }

echo "\n== ح. توازن الدفتر النهائي ==\n";
$debit = (float) App\Models\LedgerEntry::sum('debit');
$credit = (float) App\Models\LedgerEntry::sum('credit');
if (abs($debit - $credit) < 0.01) { ok('الدفتر متوازن بعد كل السيناريوهات', "{$debit} = {$credit}"); }
else { gap('critical', 'الدفتر غير متوازن', "{$debit} ≠ {$credit}"); }

echo "\n==========================================\n";
echo "خلاصة: ".count($gaps)." فجوة متبقية\n";
foreach ($gaps as $i => $g) { echo ($i + 1).". [{$g['severity']}] {$g['title']}\n"; }
