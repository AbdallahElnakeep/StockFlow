@extends('layouts.app')
@section('title', 'كشف حساب — '.$customer->name)

@section('content')
<div class="page-header">
            <button type="button" class="btn btn-secondary btn-sm export-pdf" onclick="window.print()">تصدير PDF</button>
    <div>
        <h1 class="page-title">كشف حساب: {{ $customer->name }}</h1>
        <p class="page-sub">الرصيد الحالي: {{ number_format($customer->balance, 2) }}</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-secondary" onclick="window.print()">🖨 طباعة</button>
    </div>
</div>

<form class="filter-bar" method="GET">
    <input class="input" name="from" type="date" value="{{ $from }}">
    <input class="input" name="to" type="date" value="{{ $to }}">
    <button class="btn btn-secondary" type="submit">عرض</button>
</form>

<div class="card">
    <table class="table">
        <thead><tr><th>التاريخ</th><th>البيان</th><th>مدين (عليه)</th><th>دائن (له)</th><th>الرصيد</th></tr></thead>
        <tbody>
        <tr>
            <td colspan="4"><strong>رصيد افتتاحي الفترة</strong></td>
            <td class="num"><strong>{{ number_format($openingBalance, 2) }}</strong></td>
        </tr>
        @forelse($events as $event)
            <tr>
                <td class="num">{{ \Carbon\Carbon::parse($event['date'])->format('Y-m-d') }}</td>
                <td>{{ $event['type'] ?? '' }} @if($event['note'] ?? '')<span style="color:var(--text-3)">— {{ $event['note'] }}</span>@endif</td>
                <td class="num">{{ number_format($event['debit'] ?? 0, 2) }}</td>
                <td class="num">{{ number_format($event['credit'] ?? 0, 2) }}</td>
                <td class="num" @if(($event['balance'] ?? 0) > 0) style="color:var(--error); font-weight:600" @endif>{{ number_format($event['balance'] ?? 0, 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="empty">لا توجد حركات في الفترة المحددة</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
