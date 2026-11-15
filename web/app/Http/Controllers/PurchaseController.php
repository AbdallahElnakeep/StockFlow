<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\PurchaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PurchaseController extends Controller
{
    public function __construct(private PurchaseService $purchaseService) {}

    public function index(Request $request)
    {
        $purchases = Purchase::with(['supplier', 'warehouse'])
            ->when($request->q, fn ($q, $v) => $q->where('number', 'like', "%{$v}%"))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('purchases.index', ['purchases' => $purchases, 'q' => $request->q]);
    }

    public function create()
    {
        return view('purchases.form', [
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(),
            'warehouses' => Warehouse::where('is_active', true)->where('type', 'main')->get(),
            'products' => Product::where('is_active', true)->orderBy('name')->get(['id', 'name', 'cost_price']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'payment_type' => ['required', 'in:cash,credit'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:500'],
            'purchase_date' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.qty' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
        ]);

        $purchase = $this->purchaseService->createPurchase($data + ['uuid' => (string) Str::uuid()], $request->user());

        return redirect()->route('purchases.index')->with('success', 'تم تسجيل فاتورة الشراء '.$purchase->number);
    }

    public function show(Purchase $purchase)
    {
        $purchase->load(['items.product', 'supplier', 'warehouse']);

        return view('purchases.show', ['purchase' => $purchase]);
    }
}
