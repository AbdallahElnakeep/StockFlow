<?php
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$base = 'http://127.0.0.1:8000';
$jar = new GuzzleHttp\Cookie\CookieJar();
$client = new GuzzleHttp\Client(['cookies' => $jar, 'http_errors' => false, 'allow_redirects' => false]);

$r = $client->get($base.'/login');
preg_match('/name="_token" value="([^"]+)"/', (string)$r->getBody(), $m);
$r = $client->post($base.'/login', ['form_params' => ['_token' => $m[1], 'phone' => '01000000001', 'password' => 'owner123']]);
echo 'login: ', $r->getStatusCode(), ' -> ', $r->getHeaderLine('Location'), "\n";

$routes = [
 '/dashboard','/invoices','/invoices/create','/returns','/returns/create','/payments','/payments/create',
 '/customers','/customers/create','/customers/import/form','/purchases','/purchases/create','/suppliers','/suppliers/create',
 '/inventory/balances','/inventory/movements','/transfers','/transfers/create','/counts','/counts/create',
 '/products','/products/create','/categories','/categories/create','/units','/units/create',
 '/reps','/loadings','/loadings/create','/settlements','/expenses','/expenses/create',
 '/reports/sales','/reports/products','/reports/reps','/reports/aging','/reports/profit','/reports/stock','/reports/cheques',
 '/users','/users/create','/roles','/roles/create','/warehouses','/warehouses/create','/settings',
];
$detail = [
 '/invoices/' => App\Models\SalesInvoice::value('id'),
 '/purchases/' => App\Models\Purchase::value('id'),
 '/transfers/' => App\Models\StockTransfer::value('id'),
 '/counts/' => App\Models\StockCount::value('id'),
 '/loadings/' => App\Models\RepLoading::value('id'),
 '/settlements/' => App\Models\RepSettlement::value('id'),
 '/warehouses/' => App\Models\Warehouse::value('id'),
 '/reps/' => App\Models\User::where('phone', '01000000002')->value('id'),
];
foreach ($detail as $p => $id) { if ($id) $routes[] = $p.$id; }
$cid = App\Models\Customer::value('id');
if ($cid) $routes[] = "/reports/statement/$cid";

$bad = [];
foreach ($routes as $path) {
    $r = $client->get($base.$path);
    $code = $r->getStatusCode();
    $note = '';
    if ($code >= 400) {
        $b = (string)$r->getBody();
        preg_match('/<title>([^<]*)<\/title>/', $b, $tm);
        $note = $tm[1] ?? substr($b, 0, 200);
    }
    echo str_pad((string)$code, 5), $path, $note ? "  << $note" : '', "\n";
    if ($code >= 400) $bad[] = "$path => $code";
}
echo "\nTotal: ", count($routes), " | BAD: ", count($bad), "\n", implode("\n", $bad), "\n";
