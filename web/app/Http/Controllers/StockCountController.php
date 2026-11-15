<?php

namespace App\Http\Controllers;

use App\Models\StockCount;
use App\Models\Warehouse;
use App\Services\StockCountService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StockCountController extends Controller
{
    public function __construct(private StockCountService $countService) {}

    public function index()
    {
        $counts = StockCount::with('warehouse')->latest()->paginate(20);

        return view('counts.index', ['counts' => $counts]);
    }

    public function create()
    {
        return view('counts.create', [
            'warehouses' => Warehouse::where('is_active', true)->whereIn('type', ['main', 'rep'])->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $count = $this->countService->create($data + ['uuid' => (string) Str::uuid()], $request->user());

        return redirect()->route('counts.show', $count)->with('success', 'تم إنشاء الجرد — سجّل الكميات الفعلية');
    }

    public function show(StockCount $count)
    {
        $count->load(['items.product', 'warehouse']);

        return view('counts.show', ['count' => $count]);
    }

    public function update(Request $request, StockCount $count)
    {
        $data = $request->validate([
            'items' => ['required', 'array'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.counted_qty' => ['nullable', 'numeric', 'min:0'],
        ]);

        $this->countService->setCounted($count, $data['items']);

        return redirect()->route('counts.show', $count)->with('success', 'تم حفظ الكميات');
    }

    public function adjust(Request $request, StockCount $count)
    {
        if ($count->items()->whereNull('counted_qty')->exists()) {
            return back()->with('error', 'لا يمكن التسوية قبل جرد كل الأصناف');
        }

        $this->countService->adjust($count, $request->user());

        return redirect()->route('counts.index')->with('success', 'تمت تسوية الجرد وتحديث الأرصدة');
    }
}
