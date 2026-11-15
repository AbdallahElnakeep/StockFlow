@extends('layouts.app')
@section('title', 'المندوبون — الأداء')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">أداء المندوبين</h1>
        <p class="page-sub">مبيعات وتحصيلات وعُهد كل مندوب — اليوم وهذا الشهر</p>
    </div>
    @can('reps.create')<div class="page-actions"><a class="btn btn-primary" href="{{ route('loadings.create') }}">تحميل عهدة</a></div>@endcan
</div>

@if($reps->isEmpty())
    <div class="card"><div class="empty">لا يوجد مندوبون — أضف مستخدمًا بدور «مندوب توزيع» من شاشة المستخدمين وستُنشأ عهدته تلقائيًا</div></div>
@else
    @foreach($reps as $rep)
        <div class="card" style="margin-bottom:14px">
            <div class="card-pad" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; border-bottom:1px solid var(--border-hair)">
                <div>
                    <strong style="font-size:15px">{{ $rep->name }}</strong>
                    <span class="num" style="color:var(--text-3); font-size:12.5px; margin-inline-start:8px">{{ $rep->phone }}</span>
                    @if(!$rep->is_active)<span class="badge badge-error" style="margin-inline-start:6px">موقوف</span>@endif
                </div>
                <div style="display:flex; gap:6px; flex-wrap:wrap">
                    @if($rep->custody_id)<a class="btn btn-secondary btn-sm" href="{{ route('warehouses.show', $rep->custody_id) }}">عهدته ({{ $rep->custody_products }} صنف / {{ number_format($rep->custody_value, 0) }})</a>@endif
                    <a class="btn btn-primary btn-sm" href="{{ route('reps.show', $rep->id) }}">العهدة والتسوية ←</a>
                </div>
            </div>
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(150px, 1fr)); gap:0">
                <div style="padding:12px 16px; border-inline-end:1px solid var(--border-hair)">
                    <div class="stat-label">مبيعات اليوم</div>
                    <div class="stat-value" style="font-size:17px">{{ number_format($rep->today_sales, 2) }}</div>
                    <div class="stat-hint">{{ $rep->today_invoices }} فاتورة · تحصيل {{ number_format($rep->today_collections, 0) }}</div>
                </div>
                <div style="padding:12px 16px; border-inline-end:1px solid var(--border-hair)">
                    <div class="stat-label">مبيعات الشهر</div>
                    <div class="stat-value" style="font-size:17px">{{ number_format($rep->month_sales, 2) }}</div>
                    <div class="stat-hint">{{ $rep->month_invoices }} فاتورة</div>
                </div>
                <div style="padding:12px 16px; border-inline-end:1px solid var(--border-hair)">
                    <div class="stat-label">ربح الشهر</div>
                    <div class="stat-value" style="font-size:17px; color:var(--success)">{{ number_format($rep->month_profit, 2) }}</div>
                </div>
                <div style="padding:12px 16px; border-inline-end:1px solid var(--border-hair)">
                    <div class="stat-label">تحصيلات الشهر</div>
                    <div class="stat-value" style="font-size:17px">{{ number_format($rep->month_collections, 2) }}</div>
                    <div class="stat-hint">مصروفات {{ number_format($rep->month_expenses, 0) }}</div>
                </div>
                <div style="padding:12px 16px">
                    <div class="stat-label">مستندات غير مُسوّاة</div>
                    <div class="stat-value" style="font-size:17px; @if($rep->unsettled_docs > 0) color:var(--warning) @endif">{{ $rep->unsettled_docs }}</div>
                    <div class="stat-hint">@if($rep->unsettled_docs > 0) تحتاج تسوية @else نظيف ✓ @endif</div>
                </div>
            </div>
        </div>
    @endforeach
@endif
@endsection
