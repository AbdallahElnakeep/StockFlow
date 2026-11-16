<?php

namespace App\Http\Controllers;

use App\Models\PurchaseItem;
use App\Models\PurchaseReturn;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\PurchaseReturnService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PurchaseReturnController extends Controller
{
    public function __construct(private PurchaseReturnService $service) {}

    public function index(Request $request)
    {
        $returns = PurchaseReturn::with(['supplier', 'warehouse'])
            ->when($request->supplier_id, fn ($q, $v) => $q->where('supplier_id', $v))
            ->when($request->type, fn ($q, $v) => $q->where('type', $v))
            ->when($request->from, fn ($q, $v) => $q->where('issued_at', '>=', $v.' 00:00:00'))
            ->when($request->to, fn ($q, $v) => $q->where('issued_at', '<=', $v.' 23:59:59'))
            ->latest('issued_at')
            ->paginate(25)
            ->withQueryString();

        return view('purchases.returns-index', [
            'returns' => $returns,
            'suppliers' => Supplier::orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['supplier_id', 'type', 'from', 'to']),
        ]);
    }

    public function create(Request $request)
    {
        $suppliers = Supplier::orderBy('name')->get(['id', 'name']);
        $warehouses = Warehouse::where('type', '!=', 'rep')->orderBy('name')->get(['id', 'name']);
        $supplierId = $request->supplier_id ? (int) $request->supplier_id : null;

        $costs = [];
        if ($supplierId) {
            $rows = PurchaseItem::whereHas('purchase', fn ($q) => $q->where('supplier_id', $supplierId))
                ->selectRaw('product_id, SUM(qty) as purchased')
                ->groupBy('product_id')
                ->get();
            $returned = \App\Models\PurchaseReturnItem::whereHas('purchaseReturn', fn ($q) => $q->where('supplier_id', $supplierId))
                ->selectRaw('product_id, SUM(qty) as returned')
                ->groupBy('product_id')
                ->pluck('returned', 'product_id');
            $costs = $rows->mapWithKeys(fn ($r) => [
                (int) $r->product_id => round((float) $r->purchased - (float) ($returned[$r->product_id] ?? 0), 3),
            ])->filter(fn ($v) => $v > 0)->all();
        }

        $products = \App\Models\Product::whereIn('id', array_keys($costs))->orderBy('name')->get(['id', 'name']);

        return view('purchases.returns-create', [
            'suppliers' => $suppliers,
            'warehouses' => $warehouses,
            'products' => $products,
            'costs' => $costs,
            'supplierId' => $supplierId,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'type' => ['required', 'in:supplier,damage'],
            'note' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.qty' => ['required', 'numeric', 'gt:0'],
        ]);

        $data['uuid'] = (string) Str::uuid();
        $data['items'] = array_values(array_filter($data['items'], fn ($i) => (float) $i['qty'] > 0));
        if (count($data['items']) === 0) {
            return redirect()->route('purchase-returns.create', ['supplier_id' => $data['supplier_id']])
                ->withErrors(['items' => 'أدخل كمية مرتجعة لصنف واحد على الأقل'])->withInput();
        }

        try {
            $return = $this->service->create($data, $request->user());
        } catch (\DomainException $e) {
            return redirect()->route('purchase-returns.create', ['supplier_id' => $data['supplier_id']])
                ->withErrors(['items' => $e->getMessage()])->withInput();
        }

        return redirect()->route('purchase-returns.show', $return)->with('success', "تم تسجيل المرتجع {$return->number}");
    }

    public function show(PurchaseReturn $purchaseReturn)
    {
        $purchaseReturn->load(['supplier', 'warehouse', 'items.product', 'creator']);

        return view('purchases.returns-show', ['return' => $purchaseReturn]);
    }
}
