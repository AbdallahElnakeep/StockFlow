<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\RepLoading;
use App\Models\StockBalance;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\RepLoadingService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RepLoadingController extends Controller
{
    public function __construct(private RepLoadingService $loadingService) {}

    public function index()
    {
        $loadings = RepLoading::with(['rep', 'fromWarehouse'])->latest()->paginate(20);

        return view('loadings.index', ['loadings' => $loadings]);
    }

    public function create()
    {
        $reps = User::whereHas('custodyWarehouse')->orderBy('name')->get();
        $fromWarehouseId = Warehouse::where('type', 'main')->orderBy('id')->value('id');

        return view('loadings.form', [
            'reps' => $reps,
            'from_warehouse_id' => $fromWarehouseId,
            'products' => Product::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'stockMap' => StockBalance::where('warehouse_id', $fromWarehouseId)->get()->keyBy('product_id'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'rep_user_id' => ['required', 'exists:users,id'],
            'from_warehouse_id' => ['required', 'exists:warehouses,id'],
            'note' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.qty' => ['required', 'numeric', 'gt:0'],
        ]);

        $loading = $this->loadingService->create($data + ['uuid' => (string) Str::uuid()], $request->user());

        return redirect()->route('loadings.show', $loading)->with('success', 'تم تحميل العهدة '.$loading->number);
    }

    public function show(RepLoading $loading)
    {
        $loading->load(['items.product', 'rep', 'fromWarehouse', 'toWarehouse']);

        return view('loadings.show', ['loading' => $loading]);
    }
}
