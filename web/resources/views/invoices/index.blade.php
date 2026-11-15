@extends('layouts.app')
@section('title', 'فواتير البيع')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">فواتير البيع</h1>
        <p class="page-sub">{{ $invoices->total() }} فاتورة</p>
    </div>
    @can('sales.create')
        <div class="page-actions"><a class="btn btn-primary" href="{{ route('invoices.create') }}">فاتورة جديدة</a></div>
    @endcan
</div>

<form class="filter-bar" method="GET">
    <input class="input" name="q" placeholder="رقم الفاتورة..." value="{{ $filters['q'] ?? '' }}">
    <select class="input" name="type">
        <option value="">الكل</option>
        <option value="cash" {{ ($filters['type'] ?? '') === 'cash' ? 'selected' : '' }}>نقدي</option>
        <option value="credit" {{ ($filters['type'] ?? '') === 'credit' ? 'selected' : '' }}>آجل</option>
    </select>
    <input class="input" name="from" type="date" value="{{ $filters['from'] ?? '' }}">
    <input class="input" name="to" type="date" value="{{ $filters['to'] ?? '' }}">
    <button class="btn btn-secondary" type="submit">تصفية</button>
</form>

<div class="card">
    @if($invoices->isEmpty())
        <div class="empty">لا توجد فواتير مطابقة</div>
    @else
        <table class="table">
            <thead><tr><th>الرقم</th><th>العميل</th><th>التاريخ</th><th>المخزن</th><th>النوع</th><th>الإجمالي</th></tr></thead>
            <tbody>
            @foreach($invoices as $invoice)
                <tr>
                    <td><a href="{{ route('invoices.show', $invoice) }}">{{ $invoice->number }}</a></td>
                    <td>{{ $invoice->customer->name }}</td>
                    <td class="num">{{ $invoice->issued_at->format('Y-m-d H:i') }}</td>
                    <td>{{ $invoice->warehouse->name }}</td>
                    <td>@if($invoice->status === 'cancelled')<span class="badge badge-error">ملغاة</span>@elseif($invoice->type === 'cash')<span class="badge badge-success">نقدي</span>@else<span class="badge badge-info">آجل</span>@endif</td>
                    <td class="num">{{ number_format($invoice->total, 2) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>

{{ $invoices->links() }}
@endsection
