<?php

namespace Tests\Feature;

use App\Models\PurchaseReturn;
use App\Models\StockBalance;
use App\Services\PurchaseService;
use App\Services\PurchaseReturnService;
use Illuminate\Support\Str;
use Tests\TestCase;

class PurchaseReturnTest extends TestCase
{
    private array $ctx;
    private $supplier;
    private $product;

    private function setupScene(float $qty = 10, float $cost = 10, string $paymentType = 'credit'): void
    {
        $this->ctx = $this->seedCompany();
        $this->supplier = $this->makeSupplier($this->ctx['company']);
        $this->product = $this->makeProduct($this->ctx['company'], $cost, $cost + 5);
        $this->actingAs($this->ctx['owner']);

        app(PurchaseService::class)->createPurchase([
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->ctx['main']->id,
            'uuid' => (string) Str::uuid(),
            'payment_type' => $paymentType,
            'items' => [['product_id' => $this->product->id, 'qty' => $qty, 'unit_cost' => $cost]],
        ], $this->ctx['owner']);
    }

    private function stockQty(): float
    {
        return (float) StockBalance::where('warehouse_id', $this->ctx['main']->id)
            ->where('product_id', $this->product->id)->value('qty');
    }

    public function test_supplier_return_reduces_balance_and_stock_and_balances_ledger(): void
    {
        $this->setupScene(10, 10, 'credit');
        $this->assertEquals(100, (float) $this->supplier->fresh()->balance);

        $return = app(PurchaseReturnService::class)->create([
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->ctx['main']->id,
            'uuid' => (string) Str::uuid(),
            'type' => 'supplier',
            'items' => [['product_id' => $this->product->id, 'qty' => 4]],
        ], $this->ctx['owner']);

        $this->assertEquals(60, (float) $this->supplier->fresh()->balance);
        $this->assertEquals(6, $this->stockQty());
        $this->assertEquals(40, (float) $return->fresh()->total);

        $ledger = \App\Models\LedgerEntry::where('ref_type', 'purchase_return')->where('ref_id', $return->id)
            ->selectRaw('SUM(debit) as d, SUM(credit) as c')->first();
        $this->assertEquals((float) $ledger->d, (float) $ledger->c);
        $this->assertEquals(40, (float) $ledger->d);
    }

    public function test_damage_return_charges_shortage_account_not_supplier(): void
    {
        $this->setupScene(5, 8, 'cash');
        $balanceBefore = (float) $this->supplier->fresh()->balance;

        $return = app(PurchaseReturnService::class)->create([
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->ctx['main']->id,
            'uuid' => (string) Str::uuid(),
            'type' => 'damage',
            'items' => [['product_id' => $this->product->id, 'qty' => 2]],
        ], $this->ctx['owner']);

        $this->assertEquals($balanceBefore, (float) $this->supplier->fresh()->balance);
        $this->assertEquals(16, (float) $return->fresh()->total);
        $this->assertEquals(3, $this->stockQty());

        $damageAccountId = \App\Models\LedgerAccount::where('code', '5100')->value('id');
        $damageDebit = (float) \App\Models\LedgerEntry::where('ref_type', 'purchase_return')->where('ref_id', $return->id)
            ->where('ledger_account_id', $damageAccountId)->sum('debit');
        $this->assertEquals(16, $damageDebit);
    }

    public function test_cannot_return_more_than_purchased_from_supplier(): void
    {
        $this->setupScene(3, 5, 'cash');

        $this->expectException(\DomainException::class);
        app(PurchaseReturnService::class)->create([
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->ctx['main']->id,
            'uuid' => (string) Str::uuid(),
            'type' => 'supplier',
            'items' => [['product_id' => $this->product->id, 'qty' => 4]],
        ], $this->ctx['owner']);
    }

    public function test_cannot_return_when_stock_insufficient(): void
    {
        $this->setupScene(10, 5, 'cash');

        app(\App\Services\SalesService::class)->createInvoice([
            'customer_id' => $this->makeCustomer($this->ctx['company'])->id,
            'warehouse_id' => $this->ctx['main']->id,
            'uuid' => (string) Str::uuid(),
            'type' => 'cash',
            'items' => [['product_id' => $this->product->id, 'qty' => 9, 'unit_price' => 10]],
        ], $this->ctx['owner']);
        $this->assertEquals(1, $this->stockQty());

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('غير متوفرة في المخزن');
        app(PurchaseReturnService::class)->create([
            'supplier_id' => $this->supplier->id,
            'warehouse_id' => $this->ctx['main']->id,
            'uuid' => (string) Str::uuid(),
            'type' => 'damage',
            'items' => [['product_id' => $this->product->id, 'qty' => 5]],
        ], $this->ctx['owner']);
    }

    public function test_duplicate_uuid_is_idempotent(): void
    {
        $this->setupScene(10, 6, 'cash');
        $uuid = (string) Str::uuid();
        $service = app(PurchaseReturnService::class);
        $payload = ['supplier_id' => $this->supplier->id, 'warehouse_id' => $this->ctx['main']->id, 'uuid' => $uuid, 'type' => 'supplier', 'items' => [['product_id' => $this->product->id, 'qty' => 2]]];

        $first = $service->create($payload, $this->ctx['owner']);
        $second = $service->create($payload, $this->ctx['owner']);

        $this->assertEquals($first->id, $second->id);
        $this->assertEquals(1, PurchaseReturn::count());
        $this->assertEquals(8, $this->stockQty());
    }
}
