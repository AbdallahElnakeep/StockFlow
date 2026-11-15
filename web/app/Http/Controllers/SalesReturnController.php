<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesReturn;
use App\Models\Warehouse;
use App\Services\SalesReturnService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SalesReturnController extends Controller
{
    public function __construct(private SalesReturnService $returnService) {}

    public function index(Request $request)
    {
        $returns = SalesReturn::with(['customer', 'rep'])
            ->when($request->q, fn ($q, $v) => $q->where('number', 'like', "%{$v}%"))
            ->latest('issued_at')
            ->paginate(20)
            ->withQueryString();

        return view('returns.index', ['returns' => $returns, 'q' => $request->q]);
    }

    public function create(Request $request)
    {
        return view('returns.form', [
            'customers' => Customer::where('is_active', true)->orderBy('name')->get(),
            'warehouses' => Warehouse::where('is_active', true)->get(),
            'products' => Product::where('is_active', true)->orderBy('name')->get(['id', 'name', 'sale_price']),
            'invoice_id' => $request->query('invoice'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'sales_invoice_id' => ['nullable', 'exists:sales_invoices,id'],
            'restock' => ['required', 'boolean'],
            'note' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.qty' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $return = $this->returnService->createReturn($data + ['uuid' => (string) Str::uuid(), 'channel' => 'web'], $request->user());

        return redirect()->route('returns.index')->with('success', 'تم تسجيل المرتجع '.$return->number);
    }
}
