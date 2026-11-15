@extends('layouts.app')
@section('title', 'حركات المخزون')

@section('content')
<div class="page-header">
            <button type="button" class="btn btn-secondary btn-sm export-pdf" onclick="window.print()">تصدير PDF</button>
    <div><h1 class="page-title">حركات المخزون</h1><p class="page-sub">{{ $movements->total() }} حركة</p></div>
</div>

<form class="filter-bar" method="GET">
    <select class="input" name="warehouse_id">
        <option value="">كل المخازن</option>
        @foreach($warehouses as $warehouse)
            <option value="{{ $warehouse->id }}" {{ ($filters['warehouse_id'] ?? '') == $warehouse->id ? 'selected' : '' }}>{{ $warehouse->name }}</option>
        @endforeach
    </select>
    <select class="input" name="ref_type">
        <option value="">كل الأنواع</option>
        @foreach($refTypes as $key => $label)
            <option value="{{ $key }}" {{ ($filters['ref_type'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
    <select class="input" name="product_id">
        <option value="">كل الأصناف</option>
        @foreach($products as $product)
            <option value="{{ $product->id }}" {{ ($filters['product_id'] ?? '') == $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
        @endforeach
    </select>
    <input class="input" name="from" type="date" value="{{ $filters['from'] ?? '' }}">
    <input class="input" name="to" type="date" value="{{ $filters['to'] ?? '' }}">
    <button class="btn btn-secondary" type="submit">تصفية</button>
</form>

<div class="card">
    @if($movements->isEmpty())
        <div class="empty">لا توجد حركات مطابقة</div>
    @else
        <table class="table">
            <thead><tr><th>التاريخ</th><th>الصنف</th><th>المخزن</th><th>النوع</th><th>الكمية</th><th>الرصيد بعدها</th></tr></thead>
            <tbody>
            @foreach($movements as $movement)
                <tr>
                    <td class="num">{{ $movement->created_at->format('m-d H:i') }}</td>
                    <td>{{ $movement->product->name }}</td>
                    <td>{{ $movement->warehouse->name }}</td>
                    <td>{{ $refTypes[$movement->ref_type] ?? $movement->ref_type }}</td>
                    <td class="num" @if($movement->qty < 0) style="color:var(--error)" @else style="color:var(--success)" @endif>{{ $movement->qty + 0 }}</td>
                    <td class="num">{{ $movement->balance_after + 0 }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>

{{ $movements->links() }}
@endsection
