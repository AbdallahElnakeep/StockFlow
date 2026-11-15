@extends('layouts.app')
@section('title', $warehouse->name)

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">{{ $warehouse->name }}</h1>
        <p class="page-sub">
            @if($warehouse->type === 'rep')
                عهدة مندوب: <strong>{{ $warehouse->rep?->name ?? '—' }}</strong> ·
            @endif
            {{ $balances->total() }} صنف · قيمة المخزون {{ number_format($totalValue, 2) }}
        </p>
    </div>
    <div class="page-actions">
        @if($warehouse->type === 'rep' && $warehouse->rep)
            @can('reps.view')<a class="btn btn-primary" href="{{ route('reps.show', $warehouse->rep) }}">متابعة العهدة والتسوية</a>@endcan
        @else
            @can('inventory.create')<a class="btn btn-secondary" href="{{ route('transfers.create') }}">تحويل من/إلى هذا المخزن</a>@endcan
            @can('inventory.create')<a class="btn btn-secondary" href="{{ route('counts.create') }}">جرد هذا المخزن</a>@endcan
        @endif
        <a class="btn btn-secondary" href="{{ route('warehouses.index') }}">كل المخازن</a>
    </div>
</div>

<div class="card" style="margin-bottom:14px">
    <div class="card-pad" style="padding-bottom:8px; font-weight:600">الأصناف بالرصيد</div>
    @if($balances->isEmpty())
        <div class="empty">لا توجد أصناف بهذا {{ $warehouse->type === 'rep' ? 'العهدة' : 'المخزن' }}</div>
    @else
        <table class="table">
            <thead><tr><th>الصنف</th><th>البراند</th><th>التصنيف</th><th>الرصيد</th><th>متوسط التكلفة</th><th>القيمة</th><th>الحد الأدنى</th></tr></thead>
            <tbody>
            @foreach($balances as $balance)
                @php($p = $balance->product)
                <tr>
                    <td>{{ $p->name }}</td>
                    <td>@if($p->brand)<span class="badge badge-info">{{ $p->brand }}</span>@else—@endif</td>
                    <td>{{ $p->category?->name ?? '—' }}</td>
                    <td class="num" style="font-weight:600">{{ $balance->qty + 0 }}</td>
                    <td class="num">{{ number_format($balance->avg_cost, 2) }}</td>
                    <td class="num">{{ number_format($balance->qty * $balance->avg_cost, 2) }}</td>
                    <td class="num" @if($balance->qty + 0 < (float) $p->min_stock) style="color:var(--warning); font-weight:600" @endif>{{ $p->min_stock + 0 }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>

{{ $balances->links() }}

<div class="card">
    <div class="card-pad" style="padding-bottom:8px; font-weight:600">آخر 20 حركة</div>
    @if($movements->isEmpty())
        <div class="empty">لا توجد حركات بعد</div>
    @else
        <table class="table">
            <thead><tr><th>التاريخ</th><th>الصنف</th><th>النوع</th><th>الكمية</th><th>الرصيد بعدها</th></tr></thead>
            <tbody>
            @foreach($movements as $movement)
                <tr>
                    <td class="num">{{ $movement->created_at->format('m-d H:i') }}</td>
                    <td>{{ $movement->product?->name ?? '؟' }}</td>
                    <td>{{ ['purchase' => 'شراء', 'sales_invoice' => 'بيع', 'sales_invoice_cancel' => 'إلغاء بيع', 'sales_return' => 'مرتجع', 'stock_transfer' => 'تحويل', 'stock_count' => 'جرد', 'rep_loading' => 'تحميل عهدة', 'rep_settlement' => 'تسوية عهدة'][$movement->ref_type] ?? $movement->ref_type }}</td>
                    <td class="num" @if($movement->qty < 0) style="color:var(--error)" @else style="color:var(--success)" @endif>{{ $movement->qty + 0 }}</td>
                    <td class="num">{{ $movement->balance_after + 0 }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
