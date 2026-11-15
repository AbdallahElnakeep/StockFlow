@extends('layouts.app')
@section('title', 'أرصدة المخزون')

@section('content')
<div class="page-header">
    <div><h1 class="page-title">أرصدة المخزون</h1><p class="page-sub">{{ $balances->total() }} رصيد</p></div>
</div>

<form class="filter-bar" method="GET">
    <select class="input" name="warehouse_id">
        <option value="">كل المخازن</option>
        @foreach($warehouses as $warehouse)
            <option value="{{ $warehouse->id }}" {{ ($filters['warehouse_id'] ?? '') == $warehouse->id ? 'selected' : '' }}>{{ $warehouse->name }}</option>
        @endforeach
    </select>
    <input class="input" name="q" placeholder="اسم الصنف..." value="{{ $filters['q'] ?? '' }}">
    <button class="btn btn-secondary" type="submit">تصفية</button>
</form>

<div class="card">
    @if($balances->isEmpty())
        <div class="empty">لا توجد أرصدة</div>
    @else
        <table class="table">
            <thead><tr><th>الصنف</th><th>المخزن</th><th>الكمية</th><th>متوسط التكلفة</th><th>القيمة</th></tr></thead>
            <tbody>
            @foreach($balances as $balance)
                <tr>
                    <td>{{ $balance->product->name }}</td>
                    <td>{{ $balance->warehouse->name }}</td>
                    <td class="num">{{ $balance->qty + 0 }}</td>
                    <td class="num">{{ number_format($balance->avg_cost, 2) }}</td>
                    <td class="num">{{ number_format($balance->qty * $balance->avg_cost, 2) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>

{{ $balances->links() }}
@endsection
