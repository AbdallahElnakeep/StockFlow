<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\StockBalance;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class SalesService
{
    public function __construct(
        private StockService $stock,
        private LedgerService $ledger,
        private DocumentNumberService $numbers,
    ) {}

    public function createInvoice(array $data, ?User $actor = null): SalesInvoice
    {
        return DB::transaction(function () use ($data, $actor) {
            $existing = SalesInvoice::where('uuid', $data['uuid'])->first();
            if ($existing) {
                return $existing;
            }

            $customer = Customer::findOrFail($data['customer_id']);
            $warehouse = Warehouse::findOrFail($data['warehouse_id']);

            $items = collect($data['items'])->map(function ($item) {
                $product = Product::findOrFail($item['product_id']);
                $item['unit_price'] = $product->sale_price;
                return $item;
            });

            $subtotal = 0;
            foreach ($items as $item) {
                $subtotal += round($item['qty'] * $item['unit_price'], 2);
            }
            $discount = round($data['discount'] ?? 0, 2);
            $total = round($subtotal - $discount, 2);

            if ($total < 0) {
                throw new \DomainException('الخصم أكبر من إجمالي الفاتورة');
            }

            $enforceCreditLimit = \App\Models\Setting::where('key', 'enforce_credit_limit')->value('value') !== '0';

            if ($enforceCreditLimit && $data['type'] === 'credit' && (float) $customer->credit_limit >= 0) {
                $creditPortion = round($total - ($data['collected_amount'] ?? 0), 2);
                if (round($customer->balance + $creditPortion, 2) > (float) $customer->credit_limit) {
                    throw new \DomainException('تجاوز حد الآجل للعميل "'.$customer->name.'" — الحد '.number_format($customer->credit_limit, 2).' والرصيد الحالي '.number_format($customer->balance, 2));
                }
            }

            $invoice = SalesInvoice::create([
                'customer_id' => $customer->id,
                'warehouse_id' => $warehouse->id,
                'rep_user_id' => $data['rep_user_id'] ?? null,
                'created_by' => $actor?->id,
                'uuid' => $data['uuid'],
                'number' => $data['number'] ?? $this->numbers->next('sales_invoice'),
                'type' => $data['type'],
                'channel' => $data['channel'] ?? 'web',
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'collected_amount' => $data['type'] === 'cash' ? $total : round($data['collected_amount'] ?? 0, 2),
                'note' => $data['note'] ?? null,
                'issued_at' => $data['issued_at'] ?? now(),
            ]);

            $totalCost = 0;
            foreach ($items as $item) {
                
                $cost = $this->stock->balanceFor($warehouse->id, $item['product_id']) > 0
                    ? (float) StockBalance::where('warehouse_id', $warehouse->id)
                        ->where('product_id', $item['product_id'])
                        ->value('avg_cost')
                    : 0.0;

                $movement = $this->stock->move(
                    warehouse: $warehouse,
                    productId: $item['product_id'],
                    qty: -(float) $item['qty'],
                    refType: 'sales_invoice',
                    refId: $invoice->id,
                    uuid: $data['uuid'].'-i-'.$item['product_id'],
                    unitCost: $cost,
                    userId: $actor?->id,
                );

                $lineTotal = round($item['qty'] * $item['unit_price'], 2);
                $totalCost += round($cost * $item['qty'], 2);

                SalesInvoiceItem::create([
                    'sales_invoice_id' => $invoice->id,
                    'product_id' => $item['product_id'],
                    'qty' => $item['qty'],
                    'unit_price' => $item['unit_price'],
                    'unit_cost' => $movement->unit_cost,
                    'total' => $lineTotal,
                ]);
            }

            $invoice->update(['total_cost' => $totalCost]);

            $creditPortion = round($total - $invoice->collected_amount, 2);
            if ($creditPortion > 0) {
                $customer->increment('balance', $creditPortion);
            }

            $cashAccount = $warehouse->type === 'rep' && $invoice->rep_user_id
                ? $this->ledger->accountIdByCode('1020')
                : $this->ledger->accountIdByCode('1010');

            $lines = [];
            if ($invoice->collected_amount > 0) {
                $lines[] = ['account_id' => $cashAccount, 'debit' => $invoice->collected_amount];
            }
            if ($creditPortion > 0) {
                $lines[] = ['account_id' => $this->ledger->accountIdByCode('1100'), 'debit' => $creditPortion];
            }
            $lines[] = ['account_id' => $this->ledger->accountIdByCode('4000'), 'credit' => $total];
            $lines[] = ['account_id' => $this->ledger->accountIdByCode('5000'), 'debit' => $totalCost];
            $lines[] = ['account_id' => $this->ledger->accountIdByCode('1200'), 'credit' => $totalCost];

            $this->ledger->post('sales_invoice', $invoice->id, $lines, $invoice->issued_at);

            return $invoice->refresh();
        });
    }

    public function cancelInvoice(SalesInvoice $invoice, string $reason, ?User $actor = null): SalesInvoice
    {
        return DB::transaction(function () use ($invoice, $reason, $actor) {
            $invoice = $invoice->refresh();

            if ($invoice->status === 'cancelled') {
                throw new \DomainException('هذه الفاتورة ملغاة بالفعل');
            }

            if ($invoice->rep_settlement_id !== null) {
                throw new \DomainException('لا يمكن إلغاء فاتورة دخلت في تسوية عهدة مغلقة');
            }

            $returnsTotal = (float) \App\Models\SalesReturn::where('sales_invoice_id', $invoice->id)->sum('total');
            if ($returnsTotal > 0) {
                throw new \DomainException('لا يمكن إلغاء فاتورة لها مرتجعات — احذف المرتجعات أولًا بالإدارة');
            }

            $customer = $invoice->customer;
            $warehouse = $invoice->warehouse;
            $invoice->load('items');

            foreach ($invoice->items as $item) {
                $this->stock->move(
                    warehouse: $warehouse,
                    productId: $item->product_id,
                    qty: (float) $item->qty,
                    refType: 'sales_invoice_cancel',
                    refId: $invoice->id,
                    uuid: $invoice->uuid.'-cancel-'.$item->product_id,
                    unitCost: (float) $item->unit_cost,
                    userId: $actor?->id,
                    note: 'إلغاء الفاتورة '.$invoice->number,
                );
            }

            $creditPortion = round($invoice->total - $invoice->collected_amount, 2);
            if ($creditPortion > 0) {
                $customer->decrement('balance', $creditPortion);
            }

            $cashAccount = $warehouse->type === 'rep' && $invoice->rep_user_id
                ? $this->ledger->accountIdByCode('1020')
                : $this->ledger->accountIdByCode('1010');

            $lines = [];
            if ($invoice->collected_amount > 0) {
                $lines[] = ['account_id' => $cashAccount, 'credit' => $invoice->collected_amount];
            }
            if ($creditPortion > 0) {
                $lines[] = ['account_id' => $this->ledger->accountIdByCode('1100'), 'credit' => $creditPortion];
            }
            $lines[] = ['account_id' => $this->ledger->accountIdByCode('4000'), 'debit' => $invoice->total];
            $lines[] = ['account_id' => $this->ledger->accountIdByCode('5000'), 'credit' => $invoice->total_cost];
            $lines[] = ['account_id' => $this->ledger->accountIdByCode('1200'), 'debit' => $invoice->total_cost];

            $this->ledger->post('sales_invoice_cancel', $invoice->id, $lines, now());

            $invoice->update([
                'status' => 'cancelled',
                'cancel_reason' => $reason,
                'cancelled_by' => $actor?->id,
                'cancelled_at' => now(),
            ]);

            return $invoice->refresh();
        });
    }
}
