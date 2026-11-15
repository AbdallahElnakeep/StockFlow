@extends('layouts.app')
@section('title', 'تقرير الأرباح')

@section('content')
<div class="page-header"><h1 class="page-title">الأرباح والخسائر</h1></div>

<form class="filter-bar" method="GET">
    <input class="input" name="from" type="date" value="{{ $from }}">
    <input class="input" name="to" type="date" value="{{ $to }}">
    <button class="btn btn-secondary" type="submit">عرض</button>
</form>

<div class="stat-grid">
    <div class="card card-pad"><div class="stat-label">الإيرادات</div><div class="stat-value">{{ number_format($sales->revenue ?? 0, 2) }}</div></div>
    <div class="card card-pad"><div class="stat-label">الخصومات</div><div class="stat-value">{{ number_format($sales->discounts ?? 0, 2) }}</div></div>
    <div class="card card-pad"><div class="stat-label">تكلفة المبيعات</div><div class="stat-value">{{ number_format($sales->cost ?? 0, 2) }}</div></div>
    <div class="card card-pad"><div class="stat-label">مردودات المبيعات</div><div class="stat-value">{{ number_format($returns, 2) }}</div></div>
    <div class="card card-pad">
        <div class="stat-label">الربح الإجمالي</div>
        <div class="stat-value" style="color:var(--success)">{{ number_format(($sales->profit ?? 0) - $returns, 2) }}</div>
        @php($marginPct = ($sales->revenue ?? 0) > 0 ? ((($sales->profit ?? 0) - $returns) / $sales->revenue) * 100 : 0)
        <div class="stat-hint">هامش {{ number_format($marginPct, 1) }}%</div>
    </div>
</div>
@endsection
