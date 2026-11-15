<?php

use Illuminate\Support\Facades\Artisan;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$base = 'http://127.0.0.1:8000';
$jar = new GuzzleHttp\Cookie\CookieJar();
$client = new GuzzleHttp\Client(['cookies' => $jar, 'http_errors' => false, 'allow_redirects' => false]);

function token($client, $jar, $base) {
    $r = $client->get($base.'/dashboard');
    preg_match('/name="csrf-token" content="([^"]+)/', $r->getBody(), $m);
    if (!empty($m[1])) {
        return $m[1];
    }
    $r = $client->get($base.'/login');
    preg_match('/name="_token" value="([^"]+)/', $r->getBody(), $m);
    return $m[1] ?? '';
}

$t = token($client, $jar, $base);
$r = $client->post($base.'/login', ['form_params' => ['_token' => $t, 'phone' => '01000000001', 'password' => 'owner123']]);
echo "login: ".$r->getStatusCode()."\n";

function post($client, $jar, $base, $path, $data) {
    $t = token($client, $jar, $base);
    $data['_token'] = $t;
    $r = $client->post($base.'/'.$path, ['form_params' => $data]);
    return $r->getStatusCode();
}

echo "supplier: ".post($client, $jar, $base, 'suppliers', ['name' => 'مورد القاهرة', 'phone' => '01222']) . "\n";
echo "product: ".post($client, $jar, $base, 'products', ['name' => 'زيت عباد الشمس 1لتر', 'cost_price' => 80, 'sale_price' => 100, 'min_stock' => 10])."\n";
echo "customer: ".post($client, $jar, $base, 'customers', ['name' => 'بقالة النور', 'phone' => '0109', 'area' => 'وسط البلد', 'credit_limit' => 500])."\n";

$product = App\Models\Product::latest('id')->first();
$customer = App\Models\Customer::latest('id')->first();
$supplier = App\Models\Supplier::latest('id')->first();
$warehouse = App\Models\Warehouse::where('type', 'main')->first();
$rep = App\Models\User::where('phone', '01000000002')->first();

echo "purchase: ".post($client, $jar, $base, 'purchases', [
    'supplier_id' => $supplier->id, 'warehouse_id' => $warehouse->id, 'payment_type' => 'cash', 'purchase_date' => '2026-11-15',
    'items' => [1 => ['product_id' => $product->id, 'qty' => 100, 'unit_cost' => 80]],
])."\n";
echo "رصيد الرئيسي: ".App\Models\StockBalance::where('product_id', $product->id)->where('warehouse_id', $warehouse->id)->value('qty')."\n";

echo "loading: ".post($client, $jar, $base, 'loadings', [
    'rep_user_id' => $rep->id, 'from_warehouse_id' => $warehouse->id,
    'items' => [1 => ['product_id' => $product->id, 'qty' => 50]],
])."\n";

$login = $client->post($base.'/api/auth/login', ['form_params' => ['phone' => '01000000002', 'password' => 'rep123', 'device_name' => 'e2e']]);
$rtok = json_decode($login->getBody(), true)['token'];
echo "rep token: ".substr($rtok, 0, 10)."...\n";

$opUuid = (string) Illuminate\Support\Str::uuid();
$invoiceOp = ['batch_uuid' => (string) Illuminate\Support\Str::uuid(), 'device_id' => 'e2e', 'operations' => [[
    'op_uuid' => $opUuid, 'type' => 'invoice',
    'data' => ['customer_id' => $customer->id, 'type' => 'cash', 'items' => [['product_id' => $product->id, 'qty' => 20, 'unit_price' => 100]]],
]]];

$r = $client->post($base.'/api/sync', ['headers' => ['Authorization' => "Bearer $rtok"], 'json' => $invoiceOp]);
echo "sync1: ".$r->getBody()."\n";

$invoiceOp['batch_uuid'] = (string) Illuminate\Support\Str::uuid();
$r = $client->post($base.'/api/sync', ['headers' => ['Authorization' => "Bearer $rtok"], 'json' => $invoiceOp]);
echo "sync2 (تكرار): ".$r->getBody()."\n";

$custody = App\Models\Warehouse::where('type', 'rep')->first();
echo "عهدة المندوب: ".App\Models\StockBalance::where('product_id', $product->id)->where('warehouse_id', $custody->id)->value('qty')."\n";
echo "فواتير بنفس uuid: ".App\Models\SalesInvoice::where('uuid', $opUuid)->count()."\n";

echo "== تسوية العهدة من الويب ==\n";
$draft = app(App\Services\SettlementService::class)->buildDraft($rep);
echo "النقد المتوقع: ".$draft['expected_cash']."\n";
echo "settle: ".post($client, $jar, $base, "reps/$rep->id/settle", ['cash_handover' => 245])."\n";
echo "عهدة بعد التسوية: ".App\Models\StockBalance::where('product_id', $product->id)->where('warehouse_id', $custody->id)->value('qty')."\n";
echo "الرئيسي بعد التسوية: ".App\Models\StockBalance::where('product_id', $product->id)->where('warehouse_id', $warehouse->id)->value('qty')."\n";

echo "== القيود المحاسبية ==\n";
foreach (App\Models\LedgerAccount::where('is_system', true)->get() as $acc) {
    $bal = App\Models\LedgerEntry::where('ledger_account_id', $acc->id)->selectRaw('SUM(debit)-SUM(credit) as b')->value('b');
    if (abs((float) $bal) > 0.001) {
        echo $acc->code.' '.$acc->name.': '.round((float) $bal, 2)."\n";
    }
}
$debitTotal = App\Models\LedgerEntry::sum('debit');
$creditTotal = App\Models\LedgerEntry::sum('credit');
echo "إجمالي مدين = دائن: ".($debitTotal == $creditTotal ? 'نعم ('.$debitTotal.')' : 'لا ('.$debitTotal.' / '.$creditTotal.')')."\n";
