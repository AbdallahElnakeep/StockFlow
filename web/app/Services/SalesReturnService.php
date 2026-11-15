<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class SalesReturnService
{
    public function __construct(
        private StockService $stock,
        private LedgerService $ledger,
        private DocumentNumberService $numbers,
    ) {}

    public function createReturn(array $data, ?User $actor = null): SalesReturn
    {
        return DB::transaction(function () use ($data, $actor) {
            $existing = SalesReturn::where('uuid', $data['uuid'])->first();
            if ($existing) {
                return $existing;
            }

            $customer = Customer::findOrFail($data['customer_id']);
            $warehouse = Warehouse::findOrFail($data['warehouse_id']);
            $restock = $data['restock'] ?? true;

            $total = 0;
            foreach ($data['items'] as $item) {
                $total += round($item['qty'] * $item['unit_price'], 2);
            }

            if (!empty($data['sales_invoice_id'])) {
                $invoice = \App\Models\SalesInvoice::findOrFail($data['sales_invoice_id']);
                $sold = $invoice->items->pluck('qty', 'product_id');
                $previouslyReturned = \App\Models\SalesReturnItem::whereHas('salesReturn', fn ($q) => $q->where('sales_invoice_id', $invoice->id)->where('id', '!=', $return->id ?? 0))
                    ->selectRaw('product_id, SUM(qty) as qty')
                    ->groupBy('product_id')
                    ->pluck('qty', 'product_id');

                foreach ($data['items'] as $item) {
                    $soldQty = (float) ($sold[$item['product_id']] ?? 0);
                    $returnedQty = (float) ($previouslyReturned[$item['product_id']] ?? 0);
                    if ($returnedQty + (float) $item['qty'] > $soldQty + 0.001) {
                        $product = \App\Models\Product::find($item['product_id']);
                        throw new \DomainException('كمية مرتجع الصنف "'.$product?->name.'" تتجاوز المُباع في الفاتورة (المُباع '.$soldQty.' / مرتجع سابقًا '.$returnedQty.')');
                    }
                }
            }

            $return = SalesReturn::create([
                'customer_id' => $customer->id,
                'sales_invoice_id' => $data['sales_invoice_id'] ?? null,
                'warehouse_id' => $warehouse->id,
                'rep_user_id' => $data['rep_user_id'] ?? null,
                'created_by' => $actor?->id,
                'uuid' => $data['uuid'],
                'number' => $this->numbers->next('sales_return'),
                'channel' => $data['channel'] ?? 'web',
                'restock' => $restock,
                'total' => $total,
                'note' => $data['note'] ?? null,
                'issued_at' => $data['issued_at'] ?? now(),
            ]);

            $costTotal = 0;
            foreach ($data['items'] as $item) {
                $cost = (float) (\App\Models\StockBalance::where('warehouse_id', $warehouse->id)
                    ->where('product_id', $item['product_id'])
                    ->value('avg_cost') ?? 0);
                $costTotal += round($cost * $item['qty'], 2);

                SalesReturnItem::create([
                    'sales_return_id' => $return->id,
                    'product_id' => $item['product_id'],
                    'qty' => $item['qty'],
                    'unit_price' => $item['unit_price'],
                    'total' => round($item['qty'] * $item['unit_price'], 2),
                ]);
            }

            if ($restock) {
                foreach ($data['items'] as $item) {
                    $this->stock->move(
                        warehouse: $warehouse,
                        productId: $item['product_id'],
                        qty: (float) $item['qty'],
                        refType: 'sales_return',
                        refId: $return->id,
                        uuid: $data['uuid'].'-i-'.$item['product_id'],
                        userId: $actor?->id,
                    );
                }
            }

            $customer->decrement('balance', $total);

            $returnFundedByRepCash = $warehouse->type === 'rep' && $return->rep_user_id;

            $lines = [
                ['account_id' => $this->ledger->accountIdByCode('4100'), 'debit' => $total],
                ['account_id' => $returnFundedByRepCash ? $this->ledger->accountIdByCode('1020') : $this->ledger->accountIdByCode('1100'), 'credit' => $total],
            ];

            if ($restock) {
                $lines[] = ['account_id' => $this->ledger->accountIdByCode('1200'), 'debit' => $costTotal];
                $lines[] = ['account_id' => $this->ledger->accountIdByCode('5000'), 'credit' => $costTotal];
            } else {
                $lines[] = ['account_id' => $this->ledger->accountIdByCode('5100'), 'debit' => $costTotal];
                $lines[] = ['account_id' => $this->ledger->accountIdByCode('1200'), 'credit' => $costTotal];
            }

            $this->ledger->post('sales_return', $return->id, $lines, $return->issued_at);

            return $return->refresh();
        });
    }
}
