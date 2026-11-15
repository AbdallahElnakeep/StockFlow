<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function balances(Request $request)
    {
        $balances = StockBalance::with(['product', 'warehouse'])
            ->where('qty', '!=', 0)
            ->when($request->warehouse_id, fn ($q, $v) => $q->where('warehouse_id', $v))
            ->when($request->q, fn ($q, $v) => $q->whereHas('product', fn ($p) => $p->where('name', 'like', "%{$v}%")))
            ->orderBy('warehouse_id')
            ->orderBy('product_id')
            ->paginate(50)
            ->withQueryString();

        return view('inventory.balances', [
            'balances' => $balances,
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
            'filters' => $request->only(['warehouse_id', 'q']),
        ]);
    }

    public function movements(Request $request)
    {
        $movements = StockMovement::with(['product', 'warehouse'])
            ->when($request->warehouse_id, fn ($q, $v) => $q->where('warehouse_id', $v))
            ->when($request->product_id, fn ($q, $v) => $q->where('product_id', $v))
            ->when($request->ref_type, fn ($q, $v) => $q->where('ref_type', $v))
            ->when($request->from, fn ($q, $v) => $q->where('created_at', '>=', $v))
            ->when($request->to, fn ($q, $v) => $q->where('created_at', '<=', $v.' 23:59:59'))
            ->latest()
            ->paginate(50)
            ->withQueryString();

        $refTypes = [
            'purchase' => 'شراء',
            'sales_invoice' => 'فاتورة بيع',
            'sales_return' => 'مرتجع',
            'stock_transfer' => 'تحويل',
            'stock_count' => 'تسوية جرد',
            'rep_loading' => 'تحميل عهدة',
            'rep_settlement' => 'تسوية عهدة',
        ];

        return view('inventory.movements', [
            'movements' => $movements,
            'warehouses' => Warehouse::orderBy('name')->get(),
            'products' => Product::orderBy('name')->get(['id', 'name']),
            'refTypes' => $refTypes,
            'filters' => $request->only(['warehouse_id', 'product_id', 'ref_type', 'from', 'to']),
        ]);
    }
}
