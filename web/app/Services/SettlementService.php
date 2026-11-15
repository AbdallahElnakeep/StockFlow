<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Payment;
use App\Models\RepSettlement;
use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use App\Models\StockBalance;
use App\Models\StockCount;
use App\Models\StockCountItem;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class SettlementService
{
    public function __construct(
        private StockService $stock,
        private LedgerService $ledger,
        private DocumentNumberService $numbers,
    ) {}

    public function buildDraft(User $rep, ?User $actor = null): array
    {
        $custody = Warehouse::where('rep_user_id', $rep->id)->where('type', 'rep')->firstOrFail();

        $invoiceQuery = SalesInvoice::where('rep_user_id', $rep->id)->whereNull('rep_settlement_id');
        $salesCash = (clone $invoiceQuery)->where('type', 'cash')->sum('total');
        $salesCredit = (clone $invoiceQuery)->where('type', 'credit')->sum(DB::raw('total - collected_amount'));
        $collections = Payment::where('rep_user_id', $rep->id)->where('direction', 'in')->whereNull('rep_settlement_id')->sum('amount');
        $returns = SalesReturn::where('rep_user_id', $rep->id)->whereNull('rep_settlement_id')->sum('total');
        $expenses = Expense::where('rep_user_id', $rep->id)->whereNull('rep_settlement_id')->sum('amount');

        $custodyBalances = StockBalance::where('warehouse_id', $custody->id)->where('qty', '>', 0)->with('product')->get();
        $returnedStockValue = $custodyBalances->sum(fn ($b) => round($b->qty * $b->avg_cost, 2));

        return [
            'rep' => $rep,
            'custody' => $custody,
            'sales_cash_total' => round($salesCash, 2),
            'sales_credit_total' => round($salesCredit, 2),
            'collections_total' => round($collections, 2),
            'returns_total' => round($returns, 2),
            'expenses_total' => round($expenses, 2),
            'returned_stock_total' => round($returnedStockValue, 2),
            'custody_balances' => $custodyBalances,
            'expected_cash' => round($salesCash + $collections - $returns - $expenses, 2),
        ];
    }

    public function settle(User $rep, float $cashHandover, array $data, ?User $actor = null): RepSettlement
    {
        return DB::transaction(function () use ($rep, $cashHandover, $data, $actor) {
            $draft = $this->buildDraft($rep);

            $mainWarehouseId = $data['main_warehouse_id'] ?? Warehouse::where('company_id', $rep->company_id)->where('type', 'main')->orderBy('id')->value('id');
            $mainWarehouse = Warehouse::findOrFail($mainWarehouseId);

            $settlement = RepSettlement::create([
                'rep_user_id' => $rep->id,
                'created_by' => $actor?->id,
                'uuid' => $data['uuid'],
                'number' => $this->numbers->next('rep_settlement'),
                'settlement_date' => now()->toDateString(),
                'sales_cash_total' => $draft['sales_cash_total'],
                'sales_credit_total' => $draft['sales_credit_total'],
                'collections_total' => $draft['collections_total'],
                'returns_total' => $draft['returns_total'],
                'expenses_total' => $draft['expenses_total'],
                'returned_stock_total' => $draft['returned_stock_total'],
                'cash_handover' => $cashHandover,
                'status' => 'settled',
                'note' => $data['note'] ?? null,
                'settled_at' => now(),
            ]);

            SalesInvoice::where('rep_user_id', $rep->id)->whereNull('rep_settlement_id')->update(['rep_settlement_id' => $settlement->id]);
            SalesReturn::where('rep_user_id', $rep->id)->whereNull('rep_settlement_id')->update(['rep_settlement_id' => $settlement->id]);
            Payment::where('rep_user_id', $rep->id)->whereNull('rep_settlement_id')->update(['rep_settlement_id' => $settlement->id]);
            Expense::where('rep_user_id', $rep->id)->whereNull('rep_settlement_id')->update(['rep_settlement_id' => $settlement->id]);

            foreach ($draft['custody_balances'] as $balance) {
                $this->stock->move($draft['custody'], $balance->product_id, -(float) $balance->qty, 'rep_settlement', $settlement->id, $data['uuid'].'-ret-'.$balance->product_id, (float) $balance->avg_cost, $actor?->id);
                $this->stock->move($mainWarehouse, $balance->product_id, (float) $balance->qty, 'rep_settlement', $settlement->id, $data['uuid'].'-ret-in-'.$balance->product_id, (float) $balance->avg_cost, $actor?->id);
            }

            $expectedCash = $draft['expected_cash'];
            $difference = round($cashHandover - $expectedCash, 2);
            $settlement->update(['difference' => $difference]);

            $cash = $this->ledger->accountIdByCode('1010');
            $withReps = $this->ledger->accountIdByCode('1020');

            $lines = [
                ['account_id' => $cash, 'debit' => $cashHandover],
                ['account_id' => $withReps, 'credit' => $expectedCash],
            ];

            if ($difference < 0) {
                $lines[] = ['account_id' => $this->ledger->accountIdByCode('5100'), 'debit' => abs($difference)];
            } elseif ($difference > 0) {
                $lines[] = ['account_id' => $this->ledger->accountIdByCode('5100'), 'credit' => $difference];
            }

            $this->ledger->post('rep_settlement', $settlement->id, $lines);

            return $settlement->refresh();
        });
    }
}
