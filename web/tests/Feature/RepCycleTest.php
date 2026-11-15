<?php

namespace Tests\Feature;

use App\Services\PaymentService;
use App\Services\PurchaseService;
use App\Services\RepLoadingService;
use App\Services\SalesReturnService;
use App\Services\SalesService;
use App\Services\SettlementService;
use App\Services\TransferService;
use Illuminate\Support\Str;
use Tests\TestCase;

class RepCycleTest extends TestCase
{
    private function loadStock(array $ctx, $product, int $qty = 100): void
    {
        app(PurchaseService::class)->createPurchase([
            'uuid' => (string) Str::uuid(),
            'supplier_id' => $this->makeSupplier($ctx['company'])->id,
            'warehouse_id' => $ctx['main']->id,
            'payment_type' => 'cash',
            'items' => [['product_id' => $product->id, 'qty' => $qty, 'unit_cost' => 10]],
        ], $ctx['owner']);
    }

    public function test_full_rep_cycle_until_settlement(): void
    {
        $ctx = $this->seedCompany();
        $this->actingAs($ctx['owner']);
        $product = $this->makeProduct($ctx['company'], 10, 15);
        $customer = $this->makeCustomer($ctx['company']);
        $this->loadStock($ctx, $product, 100);

        app(RepLoadingService::class)->create([
            'uuid' => (string) Str::uuid(),
            'rep_user_id' => $ctx['rep']->id,
            'from_warehouse_id' => $ctx['main']->id,
            'items' => [['product_id' => $product->id, 'qty' => 50]],
        ], $ctx['owner']);

        $this->assertEquals(50, $this->balanceOf($ctx['main'], $product->id));
        $this->assertEquals(50, $this->balanceOf($ctx['custody'], $product->id));

        app(SalesService::class)->createInvoice([
            'uuid' => (string) Str::uuid(),
            'customer_id' => $customer->id,
            'warehouse_id' => $ctx['custody']->id,
            'rep_user_id' => $ctx['rep']->id,
            'type' => 'cash',
            'channel' => 'mobile',
            'items' => [['product_id' => $product->id, 'qty' => 20, 'unit_price' => 15]],
        ], $ctx['rep']);

        $this->assertEquals(30, $this->balanceOf($ctx['custody'], $product->id));
        $this->assertEquals(300, $this->accountBalance($ctx['company'], '1020'));

        app(PaymentService::class)->createPayment([
            'uuid' => (string) Str::uuid(),
            'payable_type' => 'customer',
            'payable_id' => $customer->id,
            'direction' => 'in',
            'method' => 'cash',
            'rep_user_id' => $ctx['rep']->id,
            'amount' => 50,
        ], $ctx['rep']);

        app(\App\Services\ExpenseService::class)->createExpense([
            'rep_user_id' => $ctx['rep']->id,
            'uuid' => (string) Str::uuid(),
            'category' => 'بنزين',
            'amount' => 30,
            'issued_at' => now(),
        ], $ctx['rep']);

        app(SalesReturnService::class)->createReturn([
            'uuid' => (string) Str::uuid(),
            'customer_id' => $customer->id,
            'warehouse_id' => $ctx['custody']->id,
            'rep_user_id' => $ctx['rep']->id,
            'channel' => 'mobile',
            'restock' => true,
            'items' => [['product_id' => $product->id, 'qty' => 5, 'unit_price' => 15]],
        ], $ctx['rep']);

        $this->assertEquals(35, $this->balanceOf($ctx['custody'], $product->id));

        $expectedCash = 300 + 50 - 75 - 30;
        $this->assertEquals(245, $expectedCash);

        $settlement = app(SettlementService::class)->settle(
            $ctx['rep'],
            240,
            ['uuid' => (string) Str::uuid()],
            $ctx['owner']
        );

        $this->assertEquals('settled', $settlement->status);
        $this->assertEqualsWithDelta(-5, (float) $settlement->difference, 0.01);
        $this->assertEquals(0, $this->balanceOf($ctx['custody'], $product->id));
        $this->assertEquals(85, $this->balanceOf($ctx['main'], $product->id));

        $this->assertEqualsWithDelta(0, $this->accountBalance($ctx['company'], '1020'), 0.01);
        $this->assertEqualsWithDelta(5, $this->accountBalance($ctx['company'], '5100'), 0.01);
        $this->assertEqualsWithDelta(240, $this->accountBalance($ctx['company'], '1010') + 1000, 0.01);
    }

    public function test_transfer_between_warehouses(): void
    {
        $ctx = $this->seedCompany();
        $this->actingAs($ctx['owner']);
        $product = $this->makeProduct($ctx['company'], 10, 15);
        $this->loadStock($ctx, $product, 100);

        $second = \App\Models\Warehouse::create(['company_id' => $ctx['company']->id, 'name' => 'مخزن 2', 'type' => 'main']);

        app(TransferService::class)->create([
            'uuid' => (string) Str::uuid(),
            'from_warehouse_id' => $ctx['main']->id,
            'to_warehouse_id' => $second->id,
            'items' => [['product_id' => $product->id, 'qty' => 30]],
        ], $ctx['owner']);

        $this->assertEquals(70, $this->balanceOf($ctx['main'], $product->id));
        $this->assertEquals(30, $this->balanceOf($second, $product->id));
    }
}
