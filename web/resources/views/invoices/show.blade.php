@extends('layouts.app')
@section('title', 'فاتورة '.$invoice->number)

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">فاتورة {{ $invoice->number }}</h1>
        <p class="page-sub">{{ $invoice->issued_at->format('Y-m-d H:i') }} · {{ $invoice->channel === 'mobile' ? 'تطبيق المندوب' : 'الإدارة' }}</p>
    </div>
    <div class="page-actions">
        @if($invoice->status !== 'cancelled')
            @can('returns.create')
                <a class="btn btn-secondary" href="{{ route('returns.create', ['invoice' => $invoice->id]) }}">إنشاء مرتجع</a>
            @endcan
            <a class="btn btn-secondary" href="{{ route('invoices.print', $invoice) }}" target="_blank">🖨 طباعة</a>
            @can('sales.delete')
                <button class="btn btn-danger" type="button" onclick="cancelInvoice()">إلغاء الفاتورة</button>
            @endcan
        @endif
        <a class="btn btn-secondary" href="{{ route('invoices.index') }}">رجوع</a>
    </div>
</div>

@if($invoice->status === 'cancelled')
    <div class="alert alert-error" style="background:var(--error-bg)">
        فاتورة ملغاة — السبب: {{ $invoice->cancel_reason }}
    </div>
@endif

@if($invoice->status !== 'cancelled' && auth()->user()->hasPermission('sales.delete'))
    <div class="card card-pad" id="cancel-box" style="display:none; max-width:520px; margin-bottom:16px">
        <form method="POST" action="{{ route('invoices.cancel', $invoice) }}" onsubmit="return confirm('سيتم إرجاع البضاعة للمخزون وعكس أثرها على العميل والدفتر — متابعة؟')">
            @csrf
            <div class="field">
                <label for="reason">سبب الإلغاء *</label>
                <input class="input" id="reason" name="reason" required placeholder="مثال: خطأ في العميل">
            </div>
            <button class="btn btn-danger" type="submit">تأكيد الإلغاء</button>
            <button class="btn btn-secondary" type="button" onclick="document.getElementById('cancel-box').style.display='none'">تراجع</button>
        </form>
    </div>
@endif

<div class="card" style="margin-bottom:14px">
    <div class="card-pad" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap:12px">
        <div><div class="stat-label">العميل</div><strong>{{ $invoice->customer->name }}</strong></div>
        <div><div class="stat-label">المخزن</div><strong>{{ $invoice->warehouse->name }}</strong></div>
        <div><div class="stat-label">النوع</div>@if($invoice->type === 'cash')<span class="badge badge-success">نقدي</span>@else<span class="badge badge-info">آجل</span>@endif</div>
        @if($invoice->rep)<div><div class="stat-label">المندوب</div><strong>{{ $invoice->rep->name }}</strong></div>@endif
    </div>
</div>

<div class="card">
    <table class="table">
        <thead><tr><th>الصنف</th><th>الكمية</th><th>السعر</th><th>التكلفة</th><th>الإجمالي</th></tr></thead>
        <tbody>
        @foreach($invoice->items as $item)
            <tr>
                <td>{{ $item->product->name }}</td>
                <td class="num">{{ $item->qty + 0 }}</td>
                <td class="num">{{ number_format($item->unit_price, 2) }}</td>
                <td class="num">{{ number_format($item->unit_cost, 2) }}</td>
                <td class="num">{{ number_format($item->total, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <div class="card-pad" style="display:flex; gap:28px; justify-content:flex-end; font-size:15px">
        <span>الإجمالي الفرعي: <strong class="num">{{ number_format($invoice->subtotal, 2) }}</strong></span>
        <span>الخصم: <strong class="num">{{ number_format($invoice->discount, 2) }}</strong></span>
        <span>الصافي: <strong class="num" style="font-size:18px">{{ number_format($invoice->total, 2) }}</strong></span>
        @if($invoice->type === 'credit')
            <span>المحصّل: <strong class="num">{{ number_format($invoice->collected_amount, 2) }}</strong></span>
        @endif
    </div>
    @if($invoice->note)
        <div class="card-pad" style="padding-top:0; color:var(--text-2)">ملاحظات: {{ $invoice->note }}</div>
    @endif
</div>
@endsection

@section('scripts')
<script>
    function cancelInvoice() {
        document.getElementById('cancel-box').style.display = 'block';
        document.getElementById('reason').focus();
    }
</script>
@endsection
