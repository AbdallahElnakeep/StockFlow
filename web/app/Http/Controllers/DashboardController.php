<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\StockBalance;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $monthStart = now()->startOfMonth();
        $dayStart = now()->startOfDay();

        $salesToday = SalesInvoice::where('issued_at', '>=', $dayStart);
        $salesMonth = SalesInvoice::where('issued_at', '>=', $monthStart);
        $isRep = $user->custodyWarehouse !== null;

        $stockValue = StockBalance::join('warehouses', 'warehouses.id', '=', 'stock_balances.warehouse_id')
            ->where('warehouses.type', 'main')
            ->selectRaw('SUM(stock_balances.qty * stock_balances.avg_cost) as value')
            ->value('value');

        $warehousesOverview = Warehouse::where('is_active', true)
            ->with(['rep'])
            ->get()
            ->map(function ($w) {
                $balances = $w->balances()->where('qty', '!=', 0)->get();
                return (object) [
                    'id' => $w->id,
                    'name' => $w->name,
                    'type' => $w->type,
                    'rep' => $w->rep,
                    'products_count' => $balances->count(),
                    'total_qty' => $balances->sum('qty'),
                    'value' => round($balances->sum(fn ($b) => $b->qty * $b->avg_cost), 2),
                    'top_items' => $balances->sortByDesc('qty')->take(3)->map(fn ($b) => (object) ['name' => $b->product?->name ?? '؟', 'qty' => $b->qty + 0]),
                ];
            });

        return view('dashboard', [
            'sales_today' => (clone $salesToday)->sum('total'),
            'invoices_today' => (clone $salesToday)->count(),
            'sales_month' => (clone $salesMonth)->sum('total'),
            'profit_month' => (clone $salesMonth)->sum(DB::raw('total - total_cost')),
            'collections_today' => Payment::where('direction', 'in')->where('issued_at', '>=', $dayStart)->sum('amount'),
            'receivables' => Customer::sum('balance'),
            'stock_value' => $stockValue ?? 0,
            'customers_debt_count' => Customer::where('balance', '>', 0)->count(),
            'warehouses_overview' => $warehousesOverview,
            'brands' => Product::where('is_active', true)->whereNotNull('brand')->distinct()->pluck('brand'),
            'low_stock' => Product::where('is_active', true)->whereColumn(\DB::raw('(SELECT COALESCE(SUM(qty),0) FROM stock_balances WHERE stock_balances.product_id = products.id)'), '<', 'products.min_stock')->count(),
            'top_products' => \DB::table('sales_invoice_items')
                ->join('sales_invoices', 'sales_invoices.id', '=', 'sales_invoice_items.sales_invoice_id')
                ->join('products', 'products.id', '=', 'sales_invoice_items.product_id')
                ->where('sales_invoices.issued_at', '>=', $monthStart)
                ->selectRaw('products.name, SUM(sales_invoice_items.qty) as qty, SUM(sales_invoice_items.total) as total')
                ->groupBy('products.name')
                ->orderByDesc('total')
                ->limit(5)
                ->get(),
            'recent_invoices' => SalesInvoice::with('customer')->latest('issued_at')->limit(7)->get(),
            'warehouses' => Warehouse::where('is_active', true)->get(),
            'is_rep' => $isRep,
        ]);
    }
}
