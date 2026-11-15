@extends('layouts.app')
@section('title', 'المشتريات')

@section('content')
<div class="page-header">
    <div><h1 class="page-title">المشتريات</h1><p class="page-sub">{{ $purchases->total() }} فاتورة شراء</p></div>
    @can('purchases.create')<div class="page-actions"><a class="btn btn-primary" href="{{ route('purchases.create') }}">فاتورة شراء جديدة</a></div>@endcan
</div>

<form class="filter-bar" method="GET">
    <input class="input" name="q" placeholder="رقم الفاتورة..." value="{{ $q }}">
    <button class="btn btn-secondary" type="submit">بحث</button>
</form>

<div class="card">
    @if($purchases->isEmpty())
        <div class="empty">لا توجد فواتير شراء</div>
    @else
        <table class="table">
            <thead><tr><th>الرقم</th><th>المورد</th><th>المخزن</th><th>التاريخ</th><th>الدفع</th><th>الإجمالي</th></tr></thead>
            <tbody>
            @foreach($purchases as $purchase)
                <tr>
                    <td><a href="{{ route('purchases.show', $purchase) }}">{{ $purchase->number }}</a></td>
                    <td>{{ $purchase->supplier->name }}</td>
                    <td>{{ $purchase->warehouse->name }}</td>
                    <td class="num">{{ $purchase->purchase_date->format('Y-m-d') }}</td>
                    <td>@if($purchase->payment_type === 'cash')<span class="badge badge-success">نقدي</span>@else<span class="badge badge-info">آجل</span>@endif</td>
                    <td class="num">{{ number_format($purchase->total, 2) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>

{{ $purchases->links() }}
@endsection
