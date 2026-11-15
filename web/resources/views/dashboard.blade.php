@extends('layouts.app')
@section('title', 'لوحة المتابعة')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">لوحة المتابعة</h1>
        <p class="page-sub">{{ now()->translatedFormat('l d F Y') }}</p>
    </div>
</div>

<div class="card" style="margin-bottom:22px">
    <div class="card-pad" style="padding-bottom:10px"><strong style="font-size:14.5px">إجراءات سريعة</strong></div>
    <div style="display:flex; flex-wrap:wrap; gap:8px; padding:10px 18px 16px">
        @can('sales.create')<a class="btn btn-primary" href="{{ route('invoices.create') }}">🧾 فاتورة بيع</a>@endcan
        @can('purchases.create')<a class="btn btn-secondary" href="{{ route('purchases.create') }}">📦 فاتورة شراء</a>@endcan
        @can('payments.create')<a class="btn btn-secondary" href="{{ route('payments.create', ['direction' => 'in']) }}">💰 تحصيل من عميل</a>@endcan
        @can('reps.create')<a class="btn btn-secondary" href="{{ route('loadings.create') }}">🚚 تحميل عهدة مندوب</a>@endcan
        @can('customers.create')<a class="btn btn-secondary" href="{{ route('customers.create') }}">👤 عميل جديد</a>@endcan
        @can('products.create')<a class="btn btn-secondary" href="{{ route('products.create') }}">🏷️ صنف جديد</a>@endcan
        @can('returns.create')<a class="btn btn-secondary" href="{{ route('returns.create') }}">↩️ مرتجع</a>@endcan
        @can('inventory.create')<a class="btn btn-secondary" href="{{ route('transfers.create') }}">🔄 تحويل مخزني</a>@endcan
        @can('inventory.create')<a class="btn btn-secondary" href="{{ route('counts.create') }}">📋 جرد</a>@endcan
    </div>
</div>

<div class="stat-grid">
    <div class="card card-pad">
        <div class="stat-label">مبيعات اليوم</div>
        <div class="stat-value">{{ number_format($sales_today, 2) }}</div>
        <div class="stat-hint">{{ $invoices_today }} فاتورة</div>
    </div>
    <div class="card card-pad">
        <div class="stat-label">مبيعات الشهر</div>
        <div class="stat-value">{{ number_format($sales_month, 2) }}</div>
        <div class="stat-hint">ربح تقديري: {{ number_format($profit_month, 2) }}</div>
    </div>
    <div class="card card-pad">
        <div class="stat-label">تحصيلات اليوم</div>
        <div class="stat-value">{{ number_format($collections_today, 2) }}</div>
    </div>
    <div class="card card-pad">
        <div class="stat-label">مديونيات العملاء</div>
        <div class="stat-value">{{ number_format($receivables, 2) }}</div>
        <div class="stat-hint">{{ $customers_debt_count }} عميل مدين</div>
    </div>
    <div class="card card-pad">
        <div class="stat-label">قيمة المخزون (بالتكلفة)</div>
        <div class="stat-value">{{ number_format($stock_value, 2) }}</div>
    </div>
    @if($low_stock > 0)
        <div class="card card-pad">
            <div class="stat-label">أصناف تحت الحد الأدنى</div>
            <div class="stat-value" style="color: var(--warning)">{{ $low_stock }}</div>
        </div>
    @endif
</div>

<div class="card" style="margin-bottom:22px">
    <div class="card-pad" style="padding-bottom:10px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px">
        <strong style="font-size:15px">المخازن والعُهد</strong>
        @if($brands->isNotEmpty())
            <div style="display:flex; gap:6px; flex-wrap:wrap">
                @foreach($brands as $brand)
                    <span class="badge badge-info">{{ $brand }}</span>
                @endforeach
            </div>
        @endif
    </div>
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap:0; border-top:1px solid var(--border-hair)">
        @foreach($warehouses_overview as $wh)
            <div style="padding:14px 18px; border-bottom:1px solid var(--border-hair); border-inline-start:1px solid var(--border-hair)">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px">
                    <a href="{{ route('warehouses.show', $wh->id) }}" style="color:inherit; text-decoration:none">{{ $wh->name }} ↗</a>
                    @if($wh->type === 'rep')
                        <span class="badge badge-warning">عهدة: {{ $wh->rep?->name ?? '—' }}</span>
                    @elseif($wh->type === 'van')
                        <span class="badge badge-info">سيارة</span>
                    @else
                        <span class="badge badge-success">مخزن رئيسي</span>
                    @endif
                </div>
                <div style="font-size:13px; color:var(--text-2)">
                    {{ $wh->products_count }} صنف · إجمالي {{ number_format($wh->total_qty + 0) }} وحدة · قيمة {{ number_format($wh->value, 2) }}
                </div>
                @if($wh->top_items->isNotEmpty())
                    <div style="font-size:12px; color:var(--text-3); margin-top:6px; line-height:1.9">
                        @foreach($wh->top_items as $item)
                            <div>• {{ $item->name }} — {{ number_format($item->qty) }}</div>
                        @endforeach
                    </div>
                @endif
                @if($wh->type === 'rep' && $wh->rep && auth()->user()->hasPermission('reps.view'))
                    <a href="{{ route('reps.show', $wh->rep) }}" style="font-size:12.5px; display:inline-block; margin-top:8px">متابعة العهدة والتسوية ←</a>
                @endif
            </div>
        @endforeach
    </div>
</div>

<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap:14px">
    <div class="card">
        <div class="card-pad" style="padding-bottom:8px; font-weight:600">أحدث الفواتير</div>
        @if($recent_invoices->isEmpty())
            <div class="empty">لا توجد فواتير بعد<br><br>@can('sales.create')<a class="btn btn-primary btn-sm" href="{{ route('invoices.create') }}">إنشاء أول فاتورة</a>@endcan</div>
        @else
            <table class="table">
                <thead><tr><th>الرقم</th><th>العميل</th><th>النوع</th><th>الإجمالي</th></tr></thead>
                <tbody>
                @foreach($recent_invoices as $invoice)
                    <tr>
                        <td><a href="{{ route('invoices.show', $invoice) }}">{{ $invoice->number }}</a></td>
                        <td>{{ $invoice->customer->name }}</td>
                        <td>@if($invoice->type === 'cash')<span class="badge badge-success">نقدي</span>@else<span class="badge badge-info">آجل</span>@endif</td>
                        <td class="num">{{ number_format($invoice->total, 2) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="card">
        <div class="card-pad" style="padding-bottom:8px; font-weight:600">الأكثر مبيعًا هذا الشهر</div>
        @if($top_products->isEmpty())
            <div class="empty">لا توجد مبيعات هذا الشهر</div>
        @else
            <table class="table">
                <thead><tr><th>الصنف</th><th>الكمية</th><th>القيمة</th></tr></thead>
                <tbody>
                @foreach($top_products as $p)
                    <tr><td>{{ $p->name }}</td><td class="num">{{ $p->qty + 0 }}</td><td class="num">{{ number_format($p->total, 2) }}</td></tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection
