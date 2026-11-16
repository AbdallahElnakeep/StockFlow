@extends('layouts.app')
@section('title', 'مرتجع مشتريات')

@section('content')
<div class="page-header">
    <button type="button" class="btn btn-secondary btn-sm export-pdf" onclick="window.print()">تصدير PDF</button>
    <div>
        <h1 class="page-title">مرتجع {{ $return->number }}</h1>
        <p class="page-sub">
            @if($return->type === 'supplier')<span class="badge badge-info">مرتجع للمورد</span>@else<span class="badge badge-error">إهلاك تالف</span>@endif
        </p>
    </div>
    <div class="page-actions">
        <a class="btn btn-secondary" href="{{ route('purchase-returns.index') }}">عودة للقائمة</a>
    </div>
</div>

<div class="card" style="padding:16px">
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:12px; margin-bottom:16px">
        <div><span class="label">المورد</span><br>{{ $return->supplier?->name }}</div>
        <div><span class="label">المخزن</span><br>{{ $return->warehouse?->name }}</div>
        <div><span class="label">أنشأه</span><br>{{ $return->creator?->name ?? '—' }}</div>
        <div><span class="label">التاريخ</span><br>{{ $return->issued_at->format('Y-m-d H:i') }}</div>
        <div><span class="label">الإجمالي</span><br><strong class="num">{{ number_format($return->total, 2) }}</strong></div>
        <div><span class="label">ملاحظة</span><br>{{ $return->note ?? '—' }}</div>
    </div>

    <table class="table">
        <thead><tr><th>الصنف</th><th>الكمية</th><th>تكلفة الوحدة</th><th>الإجمالي</th></tr></thead>
        <tbody>
        @foreach($return->items as $item)
            <tr>
                <td>{{ $item->product?->name }}</td>
                <td class="num">{{ $item->qty + 0 }}</td>
                <td class="num">{{ number_format($item->unit_cost, 2) }}</td>
                <td class="num">{{ number_format($item->total, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
