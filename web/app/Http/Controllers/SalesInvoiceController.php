<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\SalesService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SalesInvoiceController extends Controller
{
    public function __construct(private SalesService $salesService) {}

    public function index(Request $request)
    {
        $invoices = SalesInvoice::with(['customer', 'warehouse'])
            ->when($request->q, fn ($q, $v) => $q->where('number', 'like', "%{$v}%"))
            ->when($request->type, fn ($q, $v) => $q->where('type', $v))
            ->when($request->from, fn ($q, $v) => $q->where('issued_at', '>=', $v))
            ->when($request->to, fn ($q, $v) => $q->where('issued_at', '<=', $v.' 23:59:59'))
            ->latest('issued_at')
            ->paginate(20)
            ->withQueryString();

        return view('invoices.index', ['invoices' => $invoices, 'filters' => $request->only(['q', 'type', 'from', 'to'])]);
    }

    public function create()
    {
        return view('invoices.form', [
            'customers' => \App\Models\Customer::where('is_active', true)->orderBy('name')->get(),
            'warehouses' => Warehouse::where('is_active', true)->whereIn('type', ['main'])->get(),
            'products' => Product::where('is_active', true)->orderBy('name')->get(['id', 'name', 'sale_price']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'type' => ['required', 'in:cash,credit'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'collected_amount' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.qty' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $invoice = $this->salesService->createInvoice($data + ['uuid' => (string) Str::uuid(), 'channel' => 'web'], $request->user());

        return redirect()->route('invoices.show', $invoice)->with('success', 'تم إنشاء الفاتورة '.$invoice->number);
    }

    public function show(SalesInvoice $invoice)
    {
        $invoice->load(['items.product', 'customer', 'warehouse', 'rep']);

        return view('invoices.show', ['invoice' => $invoice]);
    }

    public function print(SalesInvoice $invoice)
    {
        abort_if($invoice->status === 'cancelled', 422, 'فاتورة ملغاة لا تُطبع');
        $invoice->load(['items.product.unit', 'customer', 'warehouse', 'rep']);

        return view('invoices.print', ['invoice' => $invoice]);
    }

    public function cancel(Request $request, SalesInvoice $invoice)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        try {
            $this->salesService->cancelInvoice($invoice, $data['reason'], $request->user());
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('invoices.show', $invoice)->with('success', 'أُلغيت الفاتورة وأُعيدت البضاعة للمخزون');
    }
}
