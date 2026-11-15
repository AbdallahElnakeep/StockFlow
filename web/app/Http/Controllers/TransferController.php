<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockBalance;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use App\Services\TransferService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TransferController extends Controller
{
    public function __construct(private TransferService $transferService) {}

    public function index()
    {
        $transfers = StockTransfer::with(['fromWarehouse', 'toWarehouse'])->latest()->paginate(20);

        return view('transfers.index', ['transfers' => $transfers]);
    }

    public function create()
    {
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();

        return view('transfers.form', [
            'warehouses' => $warehouses,
            'products' => Product::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'stockMap' => StockBalance::selectRaw('warehouse_id, product_id, qty')->get()->groupBy('warehouse_id'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'from_warehouse_id' => ['required', 'exists:warehouses,id', 'different:to_warehouse_id'],
            'to_warehouse_id' => ['required', 'exists:warehouses,id'],
            'note' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.qty' => ['required', 'numeric', 'gt:0'],
        ]);

        $this->transferService->create($data + ['uuid' => (string) Str::uuid()], $request->user());

        return redirect()->route('transfers.index')->with('success', 'تم تنفيذ التحويل');
    }

    public function show(StockTransfer $transfer)
    {
        $transfer->load(['items.product', 'fromWarehouse', 'toWarehouse']);

        return view('transfers.show', ['transfer' => $transfer]);
    }
}
