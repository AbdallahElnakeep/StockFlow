<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use App\Models\StockBalance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function sales(Request $request)
    {
        $from = $request->from ?? now()->startOfMonth()->toDateString();
        $to = $request->to ?? now()->toDateString();

        $invoices = SalesInvoice::with('customer')
            ->whereBetween('issued_at', [$from, $to.' 23:59:59'])
            ->latest('issued_at')
            ->paginate(30)
            ->withQueryString();

        $totals = SalesInvoice::whereBetween('issued_at', [$from, $to.' 23:59:59'])
            ->selectRaw('COUNT(*) as cnt, SUM(total) as total, SUM(total_cost) as cost, SUM(total - total_cost) as profit')
            ->first();

        return view('reports.sales', compact('invoices', 'totals', 'from', 'to'));
    }

    public function products(Request $request)
    {
        $from = $request->from ?? now()->startOfMonth()->toDateString();
        $to = $request->to ?? now()->toDateString();

        $rows = DB::table('sales_invoice_items')
            ->join('sales_invoices', 'sales_invoices.id', '=', 'sales_invoice_items.sales_invoice_id')
            ->join('products', 'products.id', '=', 'sales_invoice_items.product_id')
            ->whereBetween('sales_invoices.issued_at', [$from, $to.' 23:59:59'])
            ->selectRaw('products.name, SUM(sales_invoice_items.qty) as qty, SUM(sales_invoice_items.total) as revenue, SUM(sales_invoice_items.qty * sales_invoice_items.unit_cost) as cost')
            ->groupBy('products.name')
            ->orderByDesc('revenue')
            ->paginate(30);

        return view('reports.products', compact('rows', 'from', 'to'));
    }

    public function reps(Request $request)
    {
        $from = $request->from ?? now()->startOfMonth()->toDateString();
        $to = $request->to ?? now()->toDateString();

        $rows = User::whereHas('custodyWarehouse')
            ->withSum(['invoices as sales_total' => fn ($q) => $q->whereBetween('issued_at', [$from, $to.' 23:59:59'])], 'total')
            ->withSum(['invoices as invoices_count' => fn ($q) => $q->whereBetween('issued_at', [$from, $to.' 23:59:59'])], DB::raw('1'))
            ->withSum(['collections as collections_total' => fn ($q) => $q->whereBetween('issued_at', [$from, $to.' 23:59:59'])], 'amount')
            ->withSum(['expenses as expenses_total' => fn ($q) => $q->whereBetween('issued_at', [$from, $to.' 23:59:59'])], 'amount')
            ->get();

        return view('reports.reps', compact('rows', 'from', 'to'));
    }

    public function aging()
    {
        $rows = Customer::where('balance', '>', 0)
            ->withCount(['invoices as open_invoices' => fn ($q) => $q->where('type', 'credit')->whereColumn('collected_amount', '<', 'total')])
            ->orderByDesc('balance')
            ->paginate(30);

        $oldestOpen = SalesInvoice::where('type', 'credit')
            ->whereColumn('collected_amount', '<', 'total')
            ->selectRaw('customer_id, MIN(issued_at) as oldest')
            ->groupBy('customer_id')
            ->pluck('oldest', 'customer_id');

        return view('reports.aging', compact('rows', 'oldestOpen'));
    }

    public function profit(Request $request)
    {
        $from = $request->from ?? now()->startOfMonth()->toDateString();
        $to = $request->to ?? now()->toDateString();

        $sales = SalesInvoice::whereBetween('issued_at', [$from, $to.' 23:59:59'])
            ->selectRaw('SUM(total) as revenue, SUM(total_cost) as cost, SUM(total - total_cost) as profit, SUM(discount) as discounts')
            ->first();

        $returns = SalesReturn::whereBetween('issued_at', [$from, $to.' 23:59:59'])->sum('total');

        return view('reports.profit', compact('sales', 'returns', 'from', 'to'));
    }

    public function statement(Request $request, Customer $customer)
    {
        $from = $request->from ?? now()->subMonths(3)->toDateString();
        $to = $request->to ?? now()->toDateString();
        $toEnd = $to.' 23:59:59';

        $openingBalance = (float) $customer->invoices()->where('type', 'credit')->where('issued_at', '<', $from)->selectRaw('SUM(total - collected_amount) as d')->value('d')
            - (float) Payment::where('payable_type', 'customer')->where('payable_id', $customer->id)->where('direction', 'in')->where('issued_at', '<', $from)->sum('amount')
            - (float) SalesReturn::where('customer_id', $customer->id)->where('issued_at', '<', $from)->sum('total');

        $events = collect();

        foreach ($customer->invoices()->whereBetween('issued_at', [$from, $toEnd])->get() as $inv) {
            $credit = (float) $inv->total - (float) $inv->collected_amount;
            $events->push([
                'date' => $inv->issued_at,
                'type' => $inv->status === 'cancelled' ? 'فاتورة ملغاة' : 'فاتورة '.$inv->number,
                'debit' => $inv->status === 'cancelled' ? 0 : $credit,
                'credit' => 0,
                'note' => $inv->type === 'cash' ? 'نقدي محصّل '.number_format($inv->collected_amount, 2) : '',
            ]);
        }

        foreach (Payment::where('payable_type', 'customer')->where('payable_id', $customer->id)->where('direction', 'in')->whereBetween('issued_at', [$from, $toEnd])->get() as $pay) {
            $events->push(['date' => $pay->issued_at, 'type' => 'تحصيل '.$pay->number, 'debit' => 0, 'credit' => (float) $pay->amount, 'note' => $pay->note ?? '']);
        }

        foreach (SalesReturn::where('customer_id', $customer->id)->whereBetween('issued_at', [$from, $toEnd])->get() as $ret) {
            $events->push(['date' => $ret->issued_at, 'type' => 'مرتجع '.$ret->number, 'debit' => 0, 'credit' => (float) $ret->total, 'note' => $ret->restock ? 'أُعيد للمخزون' : 'تالف']);
        }

        $events = $events->sortBy(fn ($e) => $e['date'])->values();

        $running = $openingBalance;
        foreach ($events as &$e) {
            $running = round($running + $e['debit'] - $e['credit'], 2);
            $e['balance'] = $running;
        }

        return view('reports.statement', compact('customer', 'events', 'openingBalance', 'from', 'to'));
    }

    public function cheques()
    {
        $cheques = Payment::where('method', 'cheque')->whereNotNull('due_date')
            ->with('payable')
            ->orderBy('due_date')
            ->get();

        return view('reports.cheques', compact('cheques'));
    }

    public function stock()
    {
        $rows = StockBalance::with(['product', 'warehouse'])
            ->whereHas('warehouse', fn ($q) => $q->where('type', 'main'))
            ->where('qty', '!=', 0)
            ->get();

        $lowStock = Product::where('is_active', true)
            ->where('min_stock', '>', 0)
            ->withSum('balances as total_qty', 'qty')
            ->get()
            ->filter(fn ($p) => (float) ($p->total_qty ?? 0) < (float) $p->min_stock);

        return view('reports.stock', compact('rows', 'lowStock'));
    }
}
