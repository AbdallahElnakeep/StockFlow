<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    public function __construct(
        private StockService $stock,
        private LedgerService $ledger,
        private DocumentNumberService $numbers,
    ) {}

    public function createPurchase(array $data, ?User $actor = null): Purchase
    {
        return DB::transaction(function () use ($data, $actor) {
            $existing = Purchase::where('uuid', $data['uuid'])->first();
            if ($existing) {
                return $existing;
            }

            $supplier = Supplier::findOrFail($data['supplier_id']);
            $warehouse = Warehouse::findOrFail($data['warehouse_id']);

            $subtotal = 0;
            foreach ($data['items'] as $item) {
                $subtotal += round($item['qty'] * $item['unit_cost'], 2);
            }
            $discount = round($data['discount'] ?? 0, 2);
            $total = round($subtotal - $discount, 2);

            $purchase = Purchase::create([
                'supplier_id' => $supplier->id,
                'warehouse_id' => $warehouse->id,
                'created_by' => $actor?->id,
                'uuid' => $data['uuid'],
                'number' => $this->numbers->next('purchase'),
                'payment_type' => $data['payment_type'],
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'note' => $data['note'] ?? null,
                'purchase_date' => $data['purchase_date'] ?? now()->toDateString(),
            ]);

            foreach ($data['items'] as $item) {
                $this->stock->move(
                    warehouse: $warehouse,
                    productId: $item['product_id'],
                    qty: (float) $item['qty'],
                    refType: 'purchase',
                    refId: $purchase->id,
                    uuid: $data['uuid'].'-i-'.$item['product_id'],
                    unitCost: (float) $item['unit_cost'],
                    userId: $actor?->id,
                );

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'],
                    'qty' => $item['qty'],
                    'unit_cost' => $item['unit_cost'],
                    'total' => round($item['qty'] * $item['unit_cost'], 2),
                ]);
            }

            if ($data['payment_type'] === 'credit') {
                $supplier->increment('balance', $total);

                $lines = [
                    ['account_id' => $this->ledger->accountIdByCode('1200'), 'debit' => $total],
                    ['account_id' => $this->ledger->accountIdByCode('2100'), 'credit' => $total],
                ];
            } else {
                $lines = [
                    ['account_id' => $this->ledger->accountIdByCode('1200'), 'debit' => $total],
                    ['account_id' => $this->ledger->accountIdByCode('1010'), 'credit' => $total],
                ];
            }

            $this->ledger->post('purchase', $purchase->id, $lines);

            return $purchase->refresh();
        });
    }
}
