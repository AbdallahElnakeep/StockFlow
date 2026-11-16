<?php

namespace App\Services;

use App\Models\Product;
use App\Models\PurchaseItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class PurchaseReturnService
{
    public function __construct(
        private StockService $stock,
        private LedgerService $ledger,
        private DocumentNumberService $numbers,
    ) {}

    public function returnableQty(Supplier $supplier, Product $product): float
    {
        $purchased = (float) PurchaseItem::where('product_id', $product->id)
            ->whereHas('purchase', fn ($q) => $q->where('supplier_id', $supplier->id))
            ->sum('qty');

        $returned = (float) PurchaseReturnItem::where('product_id', $product->id)
            ->whereHas('purchaseReturn', fn ($q) => $q->where('supplier_id', $supplier->id))
            ->sum('qty');

        return round($purchased - $returned, 3);
    }

    public function serverUnitCost(Supplier $supplier, Product $product): float
    {
        $row = PurchaseItem::where('product_id', $product->id)
            ->whereHas('purchase', fn ($q) => $q->where('supplier_id', $supplier->id))
            ->selectRaw('SUM(qty * unit_cost) as value_sum, SUM(qty) as qty_sum')
            ->first();

        $qty = (float) ($row->qty_sum ?? 0);
        if ($qty <= 0) {
            return 0.0;
        }

        return round(((float) $row->value_sum) / $qty, 2);
    }

    public function create(array $data, ?User $actor = null): PurchaseReturn
    {
        return DB::transaction(function () use ($data, $actor) {
            $existing = PurchaseReturn::where('uuid', $data['uuid'])->first();
            if ($existing) {
                return $existing;
            }

            $supplier = Supplier::findOrFail($data['supplier_id']);
            $warehouse = Warehouse::where('type', '!=', 'rep')->findOrFail($data['warehouse_id']);

            if (count($data['items']) === 0) {
                throw new \DomainException('أضف صنفًا واحدًا على الأقل');
            }

            $total = 0;
            $lines = [];
            foreach ($data['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                $qty = (float) $item['qty'];
                if ($qty <= 0) {
                    throw new \DomainException("كمية مرتجع الصنف \"{$product->name}\" يجب أن تكون أكبر من صفر");
                }

                $returnable = $this->returnableQty($supplier, $product);
                if ($qty > $returnable) {
                    throw new \DomainException("كمية مرتجع الصنف \"{$product->name}\" تتجاوز الصالح للإرجاع للمورد (الصالح {$returnable})");
                }

                $available = (float) \App\Models\StockBalance::where('warehouse_id', $warehouse->id)
                    ->where('product_id', $product->id)
                    ->value('qty') ?? 0;
                if ($qty > $available) {
                    throw new \DomainException("الكمية غير متوفرة في المخزن \"{$warehouse->name}\" (المتاح {$available})");
                }

                $unitCost = $this->serverUnitCost($supplier, $product);
                $lineTotal = round($qty * $unitCost, 2);
                $total += $lineTotal;
                $lines[] = ['product' => $product, 'qty' => $qty, 'unit_cost' => $unitCost, 'total' => $lineTotal];
            }

            $return = PurchaseReturn::create([
                'supplier_id' => $supplier->id,
                'warehouse_id' => $warehouse->id,
                'created_by' => $actor?->id,
                'uuid' => $data['uuid'],
                'number' => $this->numbers->next('purchase_return'),
                'type' => $data['type'],
                'total' => $total,
                'note' => $data['note'] ?? null,
                'issued_at' => now(),
            ]);

            foreach ($lines as $line) {
                $this->stock->move(
                    warehouse: $warehouse,
                    productId: $line['product']->id,
                    qty: -$line['qty'],
                    refType: 'purchase_return',
                    refId: $return->id,
                    uuid: $data['uuid'].'-i-'.$line['product']->id,
                    userId: $actor?->id,
                );

                PurchaseReturnItem::create([
                    'purchase_return_id' => $return->id,
                    'product_id' => $line['product']->id,
                    'qty' => $line['qty'],
                    'unit_cost' => $line['unit_cost'],
                    'total' => $line['total'],
                ]);
            }

            if ($data['type'] === 'supplier') {
                $supplier->decrement('balance', $total);

                $ledgerLines = [
                    ['account_id' => $this->ledger->accountIdByCode('2100'), 'debit' => $total],
                    ['account_id' => $this->ledger->accountIdByCode('1200'), 'credit' => $total],
                ];
            } else {
                $ledgerLines = [
                    ['account_id' => $this->ledger->accountIdByCode('5100'), 'debit' => $total],
                    ['account_id' => $this->ledger->accountIdByCode('1200'), 'credit' => $total],
                ];
            }

            $this->ledger->post('purchase_return', $return->id, $ledgerLines);

            return $return->refresh();
        });
    }
}
