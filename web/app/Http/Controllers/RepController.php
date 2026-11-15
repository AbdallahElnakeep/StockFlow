<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Payment;
use App\Models\SalesInvoice;
use App\Models\StockBalance;
use App\Models\User;
use App\Services\SettlementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RepController extends Controller
{
    public function __construct(private SettlementService $settlementService) {}

    public function index(Request $request)
    {
        $dayStart = now()->startOfDay();
        $monthStart = now()->startOfMonth();

        $reps = User::whereHas('custodyWarehouse')
            ->with('custodyWarehouse')
            ->orderBy('name')
            ->get()
            ->map(function ($rep) use ($dayStart, $monthStart) {
                $custody = $rep->custodyWarehouse;
                $custodyBalances = $custody ? $custody->balances()->where('qty', '!=', 0)->get() : collect();

                $monthInvoices = SalesInvoice::where('rep_user_id', $rep->id)->where('issued_at', '>=', $monthStart);
                $todayInvoices = SalesInvoice::where('rep_user_id', $rep->id)->where('issued_at', '>=', $dayStart);
                $monthCollections = Payment::where('rep_user_id', $rep->id)->where('direction', 'in')->where('issued_at', '>=', $monthStart);
                $todayCollections = Payment::where('rep_user_id', $rep->id)->where('direction', 'in')->where('issued_at', '>=', $dayStart);
                $monthExpenses = Expense::where('rep_user_id', $rep->id)->where('issued_at', '>=', $monthStart);
                $unsettledCount = SalesInvoice::where('rep_user_id', $rep->id)->whereNull('rep_settlement_id')->count();

                return (object) [
                    'id' => $rep->id,
                    'name' => $rep->name,
                    'phone' => $rep->phone,
                    'is_active' => $rep->is_active,
                    'custody_id' => $custody?->id,
                    'custody_products' => $custodyBalances->count(),
                    'custody_value' => round($custodyBalances->sum(fn ($b) => $b->qty * $b->avg_cost), 2),
                    'today_sales' => (clone $todayInvoices)->sum('total'),
                    'today_invoices' => (clone $todayInvoices)->count(),
                    'today_collections' => (clone $todayCollections)->sum('amount'),
                    'month_sales' => (clone $monthInvoices)->sum('total'),
                    'month_invoices' => (clone $monthInvoices)->count(),
                    'month_profit' => (clone $monthInvoices)->sum(DB::raw('total - total_cost')),
                    'month_collections' => (clone $monthCollections)->sum('amount'),
                    'month_expenses' => (clone $monthExpenses)->sum('amount'),
                    'unsettled_docs' => $unsettledCount,
                ];
            });

        return view('reps.index', ['reps' => $reps]);
    }

    public function show(Request $request, User $user, SettlementService $settlementService)
    {
        $draft = $settlementService->buildDraft($user, $request->user());

        $invoices = SalesInvoice::where('rep_user_id', $user->id)
            ->whereNull('rep_settlement_id')
            ->with('customer')
            ->latest('issued_at')
            ->limit(20)
            ->get();

        $payments = Payment::where('rep_user_id', $user->id)->whereNull('rep_settlement_id')->latest()->limit(20)->get();
        $expenses = Expense::where('rep_user_id', $user->id)->whereNull('rep_settlement_id')->latest()->limit(20)->get();

        return view('reps.show', [
            'rep' => $user,
            'draft' => $draft,
            'invoices' => $invoices,
            'payments' => $payments,
            'expenses' => $expenses,
            'main_warehouses' => \App\Models\Warehouse::where('type', 'main')->orderBy('id')->get(),
        ]);
    }

    public function settle(Request $request, User $user)
    {
        abort_unless($user->custodyWarehouse()->exists(), 404);

        $data = $request->validate([
            'cash_handover' => ['required', 'numeric', 'min:0'],
            'main_warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $settlement = $this->settlementService->settle(
            $user,
            (float) $data['cash_handover'],
            $data + ['uuid' => (string) \Illuminate\Support\Str::uuid()],
            $request->user()
        );

        return redirect()->route('settlements.show', $settlement)->with('success', 'تمت تسوية عهدة '.$user->name.' — الفرق: '.number_format($settlement->difference, 2));
    }
}
