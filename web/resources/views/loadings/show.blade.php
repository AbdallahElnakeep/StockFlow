@extends('layouts.app')
@section('title', 'تحميل '.$loading->number)

@section('content')
<div class="page-header">
    <h1 class="page-title">تحميل عهدة {{ $loading->number }}</h1>
    <div class="page-actions"><a class="btn btn-secondary" href="{{ route('loadings.index') }}">رجوع</a></div>
</div>

<div class="card" style="margin-bottom:14px">
    <div class="card-pad" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap:12px">
        <div><div class="stat-label">المندوب</div><strong>{{ $loading->rep->name }}</strong></div>
        <div><div class="stat-label">من</div><strong>{{ $loading->fromWarehouse->name }}</strong></div>
        <div><div class="stat-label">إلى (عهدة)</div><strong>{{ $loading->toWarehouse->name }}</strong></div>
        <div><div class="stat-label">قيمة العهدة بالتكلفة</div><strong class="num">{{ number_format($loading->total_cost, 2) }}</strong></div>
        <div><div class="stat-label">التاريخ</div><strong class="num">{{ $loading->created_at->format('Y-m-d H:i') }}</strong></div>
    </div>
</div>

<div class="card">
    <table class="table">
        <thead><tr><th>الصنف</th><th>الكمية</th><th>التكلفة</th><th>الإجمالي</th></tr></thead>
        <tbody>
        @foreach($loading->items as $item)
            <tr>
                <td>{{ $item->product->name }}</td>
                <td class="num">{{ $item->qty + 0 }}</td>
                <td class="num">{{ number_format($item->unit_cost, 2) }}</td>
                <td class="num">{{ number_format($item->qty * $item->unit_cost, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
