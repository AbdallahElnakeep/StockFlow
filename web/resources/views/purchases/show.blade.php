@extends('layouts.app')
@section('title', 'فاتورة شراء '.$purchase->number)

@section('content')
<div class="page-header">
    <h1 class="page-title">فاتورة شراء {{ $purchase->number }}</h1>
    <div class="page-actions"><a class="btn btn-secondary" href="{{ route('purchases.index') }}">رجوع</a></div>
</div>

<div class="card" style="margin-bottom:14px">
    <div class="card-pad" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap:12px">
        <div><div class="stat-label">المورد</div><strong>{{ $purchase->supplier->name }}</strong></div>
        <div><div class="stat-label">المخزن</div><strong>{{ $purchase->warehouse->name }}</strong></div>
        <div><div class="stat-label">التاريخ</div><strong class="num">{{ $purchase->purchase_date->format('Y-m-d') }}</strong></div>
        <div><div class="stat-label">الدفع</div>@if($purchase->payment_type === 'cash')<span class="badge badge-success">نقدي</span>@else<span class="badge badge-info">آجل</span>@endif</div>
    </div>
</div>

<div class="card">
    <table class="table">
        <thead><tr><th>الصنف</th><th>الكمية</th><th>التكلفة</th><th>الإجمالي</th></tr></thead>
        <tbody>
        @foreach($purchase->items as $item)
            <tr>
                <td>{{ $item->product->name }}</td>
                <td class="num">{{ $item->qty + 0 }}</td>
                <td class="num">{{ number_format($item->unit_cost, 2) }}</td>
                <td class="num">{{ number_format($item->total, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <div class="card-pad" style="display:flex; gap:28px; justify-content:flex-end; font-size:15px">
        <span>الإجمالي الفرعي: <strong class="num">{{ number_format($purchase->subtotal, 2) }}</strong></span>
        <span>الخصم: <strong class="num">{{ number_format($purchase->discount, 2) }}</strong></span>
        <span>الصافي: <strong class="num" style="font-size:18px">{{ number_format($purchase->total, 2) }}</strong></span>
    </div>
    @if($purchase->note)<div class="card-pad" style="padding-top:0; color:var(--text-2)">ملاحظات: {{ $purchase->note }}</div>@endif
</div>
@endsection
