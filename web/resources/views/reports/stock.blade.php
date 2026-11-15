@extends('layouts.app')
@section('title', 'تقرير المخزون')

@section('content')
<div class="page-header"><button type="button" class="btn btn-secondary btn-sm export-pdf" onclick="window.print()">تصدير PDF</button><h1 class="page-title">قيمة المخزون ونقص الأصناف</h1></div>

@if($lowStock->isNotEmpty())
    <div class="card" style="margin-bottom:14px">
        <div class="card-pad" style="padding-bottom:8px; font-weight:600; color:var(--warning)">أصناف تحت الحد الأدنى</div>
        <table class="table">
            <thead><tr><th>الصنف</th><th>الرصيد الحالي</th><th>الحد الأدنى</th><th>النقص</th></tr></thead>
            <tbody>
            @foreach($lowStock as $product)
                <tr>
                    <td>{{ $product->name }}</td>
                    <td class="num">{{ $product->total_qty + 0 }}</td>
                    <td class="num">{{ $product->min_stock + 0 }}</td>
                    <td class="num" style="color:var(--error); font-weight:600">{{ number_format($product->min_stock - $product->total_qty, 2) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endif

<div class="card">
    @if($rows->isEmpty())
        <div class="empty">لا يوجد مخزون</div>
    @else
        <table class="table">
            <thead><tr><th>الصنف</th><th>المخزن</th><th>الكمية</th><th>متوسط التكلفة</th><th>القيمة</th></tr></thead>
            <tbody>
            @foreach($rows as $row)
                <tr>
                    <td>{{ $row->product->name }}</td>
                    <td>{{ $row->warehouse->name }}</td>
                    <td class="num">{{ $row->qty + 0 }}</td>
                    <td class="num">{{ number_format($row->avg_cost, 2) }}</td>
                    <td class="num">{{ number_format($row->qty * $row->avg_cost, 2) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
