<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function show(Warehouse $warehouse)
    {
        $balances = $warehouse->balances()
            ->where('qty', '!=', 0)
            ->with(['product.category'])
            ->orderByDesc('qty')
            ->paginate(30);

        $movements = $warehouse->movements()
            ->with('product:id,name')
            ->latest()
            ->limit(20)
            ->get();

        $totalValue = $warehouse->balances()->where('qty', '!=', 0)->get()->sum(fn ($b) => $b->qty * $b->avg_cost);

        return view('warehouses.show', [
            'warehouse' => $warehouse,
            'balances' => $balances,
            'movements' => $movements,
            'totalValue' => $totalValue,
        ]);
    }

    public function index()
    {
        $warehouses = Warehouse::with('rep')->orderBy('type')->orderBy('name')->paginate(20);

        return view('warehouses.index', ['warehouses' => $warehouses]);
    }

    public function create()
    {
        return view('warehouses.form', [
            'warehouse' => new Warehouse(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['rep_user_id'] = null;
        $data['type'] = $data['type'] === 'van' ? 'van' : 'main';

        Warehouse::create($data);

        return redirect()->route('warehouses.index')->with('success', 'تم إضافة المخزن');
    }

    public function edit(Warehouse $warehouse)
    {
        return view('warehouses.form', [
            'warehouse' => $warehouse,
        ]);
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        $data = $this->validated($request, $warehouse->id);

        if ($warehouse->type === 'rep') {
            $data['name'] = $warehouse->name;
            $data['type'] = 'rep';
            $data['rep_user_id'] = $warehouse->rep_user_id;
        } else {
            if ($warehouse->balances()->where('qty', '!=', 0)->exists() && $data['is_active'] ?? true) {
                if (($data['type'] ?? $warehouse->type) !== $warehouse->type) {
                    return back()->with('error', 'لا يمكن تغيير نوع مخزن به أرصدة');
                }
            }
            $data['rep_user_id'] = null;
            $data['type'] = ($data['type'] ?? $warehouse->type) === 'van' ? 'van' : 'main';
        }

        $warehouse->update($data);

        return redirect()->route('warehouses.index')->with('success', 'تم تحديث المخزن');
    }

    public function destroy(Warehouse $warehouse)
    {
        if ($warehouse->type === 'rep') {
            return back()->with('error', 'عهد المندوبين تُدار تلقائيًا من شاشة المستخدمين ولا تُحذف من هنا');
        }

        if ($warehouse->balances()->where('qty', '!=', 0)->exists()) {
            return back()->with('error', 'لا يمكن حذف مخزن به أرصدة — انقل البضاعة أولًا أو أوقف المخزن');
        }

        if ($warehouse->stockTransfersFrom()->exists() || $warehouse->stockTransfersTo()->exists() || $warehouse->purchases()->exists()) {
            return back()->with('error', 'المخزن مرتبط بحركات سابقة — يمكن إيقافه بدلًا من حذفه');
        }

        $warehouse->delete();

        return redirect()->route('warehouses.index')->with('success', 'تم حذف المخزن');
    }

    private function validated(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['nullable', 'in:main,van'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }
}
