<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Supplier;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $paymentService) {}

    public function index(Request $request)
    {
        $payments = Payment::with(['rep', 'payable'])
            ->when($request->direction, fn ($q, $v) => $q->where('direction', $v))
            ->latest('issued_at')
            ->paginate(20)
            ->withQueryString();

        return view('payments.index', ['payments' => $payments, 'direction' => $request->direction]);
    }

    public function create(Request $request)
    {
        return view('payments.form', [
            'customers' => Customer::where('balance', '>', 0)->orderBy('name')->get(),
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(),
            'direction' => $request->query('direction', 'in'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'direction' => ['required', 'in:in,out'],
            'payable_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'method' => ['required', 'in:cash,cheque,transfer'],
            'cheque_no' => ['nullable', 'string', 'max:60'],
            'due_date' => ['nullable', 'date', 'required_if:method,cheque'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $data['payable_type'] = $data['direction'] === 'in' ? 'customer' : 'supplier';

        $payableClass = $data['payable_type'] === 'customer' ? Customer::class : Supplier::class;
        abort_unless($payableClass::whereKey($data['payable_id'])->exists(), 422, 'الحساب غير موجود');

        $payment = $this->paymentService->createPayment($data + ['uuid' => (string) Str::uuid()], $request->user());

        return redirect()->route('payments.index')->with('success', 'تم تسجيل '.($data['direction'] === 'in' ? 'التحصيل' : 'السداد').' '.$payment->number);
    }
}
