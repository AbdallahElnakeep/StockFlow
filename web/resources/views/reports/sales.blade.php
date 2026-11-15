@extends('layouts.app')
@section('title', 'تقرير المبيعات')

@section('content')
<div class="page-header"><button type="button" class="btn btn-secondary btn-sm export-pdf" onclick="window.print()">تصدير PDF</button><h1 class="page-title">تقرير المبيعات</h1></div>

<form class="filter-bar" method="GET">
    <input class="input" name="from" type="date" value="{{ $from }}">
    <input class="input" name="to" type="date" value="{{ $to }}">
    <button class="btn btn-secondary" type="submit">عرض</button>
</form>

<div class="stat-grid">
    <div class="card card-pad"><div class="stat-label">عدد الفواتير</div><div class="stat-value">{{ $totals->cnt ?? 0 }}</div></div>
    <div class="card card-pad"><div class="stat-label">إجمالي المبيعات</div><div class="stat-value">{{ number_format($totals->total ?? 0, 2) }}</div></div>
    <div class="card card-pad"><div class="stat-label">التكلفة</div><div class="stat-value">{{ number_format($totals->cost ?? 0, 2) }}</div></div>
    <div class="card card-pad"><div class="stat-label">الربح</div><div class="stat-value" style="color:var(--success)">{{ number_format($totals->profit ?? 0, 2) }}</div></div>
</div>

<div class="card">
    @if($invoices->isEmpty())
        <div class="empty">لا توجد فواتير في هذه الفترة</div>
    @else
        <table class="table">
            <thead><tr><th>الرقم</th><th>التاريخ</th><th>العميل</th><th>النوع</th><th>الإجمالي</th><th>التكلفة</th><th>الربح</th></tr></thead>
            <tbody>
            @foreach($invoices as $invoice)
                <tr>
                    <td><a href="{{ route('invoices.show', $invoice) }}">{{ $invoice->number }}</a></td>
                    <td class="num">{{ $invoice->issued_at->format('Y-m-d') }}</td>
                    <td>{{ $invoice->customer->name }}</td>
                    <td>@if($invoice->type === 'cash')<span class="badge badge-success">نقدي</span>@else<span class="badge badge-info">آجل</span>@endif</td>
                    <td class="num">{{ number_format($invoice->total, 2) }}</td>
                    <td class="num">{{ number_format($invoice->total_cost, 2) }}</td>
                    <td class="num" style="color:var(--success)">{{ number_format($invoice->total - $invoice->total_cost, 2) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>

{{ $invoices->links() }}
@endsection
