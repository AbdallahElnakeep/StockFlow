@extends('layouts.app')
@section('title', 'تسوية '.$settlement->number)

@section('content')
<div class="page-header">
    <h1 class="page-title">تسوية عهدة {{ $settlement->number }}</h1>
    <div class="page-actions"><a class="btn btn-secondary" href="{{ route('settlements.index') }}">رجوع</a></div>
</div>

<div class="stat-grid">
    <div class="card card-pad"><div class="stat-label">مبيعات نقدية</div><div class="stat-value">{{ number_format($settlement->sales_cash_total, 2) }}</div></div>
    <div class="card card-pad"><div class="stat-label">تحصيلات</div><div class="stat-value">{{ number_format($settlement->collections_total, 2) }}</div></div>
    <div class="card card-pad"><div class="stat-label">مرتجعات</div><div class="stat-value">{{ number_format($settlement->returns_total, 2) }}</div></div>
    <div class="card card-pad"><div class="stat-label">مصروفات</div><div class="stat-value">{{ number_format($settlement->expenses_total, 2) }}</div></div>
    <div class="card card-pad"><div class="stat-label">قيمة بضاعة مُرجعة</div><div class="stat-value">{{ number_format($settlement->returned_stock_total, 2) }}</div></div>
    <div class="card card-pad"><div class="stat-label">النقد المُسلَّم</div><div class="stat-value">{{ number_format($settlement->cash_handover, 2) }}</div></div>
    <div class="card card-pad">
        <div class="stat-label">الفرق</div>
        <div class="stat-value" @if($settlement->difference < 0) style="color:var(--error)" @endif>{{ number_format($settlement->difference, 2) }}</div>
        @if($settlement->difference < 0)<div class="stat-hint">عجز قُيّد على حساب التالف والعجز</div>@endif
    </div>
</div>

<div class="card card-pad" style="max-width:640px">
    <div><div class="stat-label">المندوب</div><strong>{{ $settlement->rep->name }}</strong></div>
    <div><div class="stat-label">التاريخ</div><strong class="num">{{ $settlement->settlement_date->format('Y-m-d') }}</strong></div>
    @if($settlement->note)<div><div class="stat-label">ملاحظات</div>{{ $settlement->note }}</div>@endif
</div>
@endsection
