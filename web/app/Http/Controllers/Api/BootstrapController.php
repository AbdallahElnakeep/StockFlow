<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BootstrapController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $custody = $user->custodyWarehouse;

        if (!$custody) {
            return response()->json(['message' => 'هذا الحساب ليس حساب مندوب'], 403);
        }

        $products = Product::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'barcode', 'sku', 'sale_price', 'unit_id']);

        $custodyBalances = StockBalance::where('warehouse_id', $custody->id)
            ->where('qty', '!=', 0)
            ->with('product:id,name,sale_price')
            ->get();

        $customers = Customer::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'phone', 'address', 'area', 'balance', 'credit_limit']);

        return response()->json([
            'synced_at' => now()->toIso8601String(),
            'custody' => [
                'warehouse_id' => $custody->id,
                'name' => $custody->name,
                'balances' => $custodyBalances->map(fn ($b) => [
                    'product_id' => $b->product_id,
                    'name' => $b->product->name,
                    'qty' => (float) $b->qty,
                    'sale_price' => (float) $b->product->sale_price,
                ]),
            ],
            'products' => $products->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'barcode' => $p->barcode,
                'sku' => $p->sku,
                'sale_price' => (float) $p->sale_price,
            ]),
            'customers' => $customers->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'phone' => $c->phone,
                'address' => $c->address,
                'area' => $c->area,
                'balance' => (float) $c->balance,
                'credit_limit' => (float) $c->credit_limit,
            ]),
        ]);
    }
}
