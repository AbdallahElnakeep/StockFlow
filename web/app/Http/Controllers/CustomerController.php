<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $customers = Customer::when($request->q, fn ($q, $v) => $q->where(fn ($w) => $w->where('name', 'like', "%{$v}%")->orWhere('phone', 'like', "%{$v}%")))
            ->when($request->area, fn ($q, $v) => $q->where('area', $v))
            ->when($request->boolean('debtors'), fn ($q) => $q->where('balance', '>', 0))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('customers.index', [
            'customers' => $customers,
            'areas' => Customer::whereNotNull('area')->distinct()->pluck('area'),
            'q' => $request->q,
            'area' => $request->area,
        ]);
    }

    public function create()
    {
        return view('customers.form', ['customer' => new Customer()]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $opening = (float) ($data['opening_balance'] ?? 0);

        $customer = Customer::create($data + ['created_by' => $request->user()->id, 'balance' => $opening]);

        if ($opening > 0) {
            app(\App\Services\OpeningBalanceService::class)->postCustomerOpening($customer, $opening);
        }

        return redirect()->route('customers.index')->with('success', 'تم إضافة العميل');
    }

    public function importForm()
    {
        return view('customers.import');
    }

    public function import(Request $request)
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:4096']]);

        $result = app(\App\Services\CustomerImportService::class)->import($request->file('file')->getRealPath(), $request->user());

        return redirect()->route('customers.index')->with('success', "استيراد العملاء: {$result['created']} أُضيف، {$result['skipped']} متجاهل (مكرر أو ناقص)");
    }

    public function edit(Customer $customer)
    {
        return view('customers.form', ['customer' => $customer]);
    }

    public function update(Request $request, Customer $customer)
    {
        $customer->update($this->validated($request));

        return redirect()->route('customers.index')->with('success', 'تم تحديث العميل');
    }

    public function destroy(Request $request, Customer $customer)
    {
        if ((float) $customer->balance != 0.0) {
            return back()->with('error', 'لا يمكن حذف عميل له رصيد — يمكن إيقافه');
        }

        $customer->update(['is_active' => false]);

        return redirect()->route('customers.index')->with('success', 'تم إيقاف العميل');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'area' => ['nullable', 'string', 'max:100'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'opening_balance' => ['nullable', 'numeric', 'min:0'],
        ]);
    }
}
