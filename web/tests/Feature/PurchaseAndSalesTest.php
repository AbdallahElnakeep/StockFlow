<?php

namespace Tests\Feature;

use App\Models\StockMovement;
use App\Services\PurchaseService;
use App\Services\SalesService;
use Illuminate\Support\Str;
use Tests\TestCase;

class PurchaseAndSalesTest extends TestCase
{
    public function test_purchase_adds_stock_updates_avg_cost_and_ledger(): void
    {
        $ctx = $this->seedCompany();
        $this->actingAs($ctx['owner']);
        $product = $this->makeProduct($ctx['company'], 10, 15);
        $supplier = $this->makeSupplier($ctx['company']);

        $purchase = app(PurchaseService::class)->createPurchase([
            'uuid' => (string) Str::uuid(),
            'supplier_id' => $supplier->id,
            'warehouse_id' => $ctx['main']->id,
            'payment_type' => 'cash',
            'items' => [['product_id' => $product->id, 'qty' => 100, 'unit_cost' => 10]],
        ], $ctx['owner']);

        $this->assertEquals(100, $this->balanceOf($ctx['main'], $product->id));
        $this->assertEquals(1000, $this->accountBalance($ctx['company'], '1200'));
        $this->assertEquals(1000, -$this->accountBalance($ctx['company'], '1010'));

        app(PurchaseService::class)->createPurchase([
            'uuid' => (string) Str::uuid(),
            'supplier_id' => $supplier->id,
            'warehouse_id' => $ctx['main']->id,
            'payment_type' => 'credit',
            'items' => [['product_id' => $product->id, 'qty' => 50, 'unit_cost' => 16]],
        ], $ctx['owner']);

        $avg = (float) \App\Models\StockBalance::where('product_id', $product->id)->value('avg_cost');
        $this->assertEqualsWithDelta(12.0, $avg, 0.01);
        $this->assertEquals(-800, $this->accountBalance($ctx['company'], '2100'));
    }

    public function test_cash_invoice_deducts_stock_and_balances_ledger(): void
    {
        $ctx = $this->seedCompany();
        $this->actingAs($ctx['owner']);
        $product = $this->makeProduct($ctx['company'], 10, 15);
        $supplier = $this->makeSupplier($ctx['company']);

        app(PurchaseService::class)->createPurchase([
            'uuid' => (string) Str::uuid(),
            'supplier_id' => $supplier->id,
            'warehouse_id' => $ctx['main']->id,
            'payment_type' => 'cash',
            'items' => [['product_id' => $product->id, 'qty' => 100, 'unit_cost' => 10]],
        ], $ctx['owner']);

        $customer = $this->makeCustomer($ctx['company']);

        $invoice = app(SalesService::class)->createInvoice([
            'uuid' => (string) Str::uuid(),
            'customer_id' => $customer->id,
            'warehouse_id' => $ctx['main']->id,
            'type' => 'cash',
            'items' => [['product_id' => $product->id, 'qty' => 20, 'unit_price' => 15]],
        ], $ctx['owner']);

        $this->assertEquals(80, $this->balanceOf($ctx['main'], $product->id));
        $this->assertEquals(0, (float) $customer->refresh()->balance);
        $this->assertEquals(-700, $this->accountBalance($ctx['company'], '1010'));
        $this->assertEquals(300, -$this->accountBalance($ctx['company'], '4000'));
        $this->assertEquals(200, $this->accountBalance($ctx['company'], '5000'));
        $this->assertEquals(800, $this->accountBalance($ctx['company'], '1200'));
    }

    public function test_credit_invoice_increases_customer_balance(): void
    {
        $ctx = $this->seedCompany();
        $this->actingAs($ctx['owner']);
        $product = $this->makeProduct($ctx['company'], 10, 15);
        $supplier = $this->makeSupplier($ctx['company']);

        app(PurchaseService::class)->createPurchase([
            'uuid' => (string) Str::uuid(),
            'supplier_id' => $supplier->id,
            'warehouse_id' => $ctx['main']->id,
            'payment_type' => 'cash',
            'items' => [['product_id' => $product->id, 'qty' => 100, 'unit_cost' => 10]],
        ], $ctx['owner']);

        $customer = $this->makeCustomer($ctx['company']);

        app(SalesService::class)->createInvoice([
            'uuid' => (string) Str::uuid(),
            'customer_id' => $customer->id,
            'warehouse_id' => $ctx['main']->id,
            'type' => 'credit',
            'collected_amount' => 100,
            'items' => [['product_id' => $product->id, 'qty' => 20, 'unit_price' => 15]],
        ], $ctx['owner']);

        $this->assertEquals(200, (float) $customer->refresh()->balance);
        $this->assertEquals(200, $this->accountBalance($ctx['company'], '1100'));
    }

    public function test_selling_more_than_stock_is_rejected(): void
    {
        $ctx = $this->seedCompany();
        $this->actingAs($ctx['owner']);
        $product = $this->makeProduct($ctx['company']);
        $customer = $this->makeCustomer($ctx['company']);

        $this->expectException(\DomainException::class);

        app(SalesService::class)->createInvoice([
            'uuid' => (string) Str::uuid(),
            'customer_id' => $customer->id,
            'warehouse_id' => $ctx['main']->id,
            'type' => 'cash',
            'items' => [['product_id' => $product->id, 'qty' => 1, 'unit_price' => 15]],
        ], $ctx['owner']);
    }

    public function test_duplicate_invoice_uuid_is_not_duplicated(): void
    {
        $ctx = $this->seedCompany();
        $this->actingAs($ctx['owner']);
        $product = $this->makeProduct($ctx['company'], 10, 15);
        $supplier = $this->makeSupplier($ctx['company']);

        app(PurchaseService::class)->createPurchase([
            'uuid' => (string) Str::uuid(),
            'supplier_id' => $supplier->id,
            'warehouse_id' => $ctx['main']->id,
            'payment_type' => 'cash',
            'items' => [['product_id' => $product->id, 'qty' => 100, 'unit_cost' => 10]],
        ], $ctx['owner']);

        $customer = $this->makeCustomer($ctx['company']);
        $uuid = (string) Str::uuid();

        $data = [
            'uuid' => $uuid,
            'customer_id' => $customer->id,
            'warehouse_id' => $ctx['main']->id,
            'type' => 'cash',
            'items' => [['product_id' => $product->id, 'qty' => 10, 'unit_price' => 15]],
        ];

        app(SalesService::class)->createInvoice($data, $ctx['owner']);
        app(SalesService::class)->createInvoice($data, $ctx['owner']);

        $this->assertEquals(1, \App\Models\SalesInvoice::where('uuid', $uuid)->count());
        $this->assertEquals(90, $this->balanceOf($ctx['main'], $product->id));
    }
}
