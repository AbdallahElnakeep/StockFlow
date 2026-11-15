@extends('layouts.app')
@section('title', 'عهدة '.$rep->name)

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">عهدة {{ $rep->name }}</h1>
        <p class="page-sub">النقد المتوقع: {{ number_format($draft['expected_cash'], 2) }} — يشمل نقدية البيع والتحصيل مطروحًا منها المرتجعات والمصروفات</p>
    </div>
    <div class="page-actions"><a class="btn btn-secondary" href="{{ route('reps.index') }}">رجوع للقائمة</a></div>
</div>

<div class="stat-grid">
    <div class="card card-pad"><div class="stat-label">مبيعات نقدية غير مُسوّاة</div><div class="stat-value">{{ number_format($draft['sales_cash_total'], 2) }}</div></div>
    <div class="card card-pad"><div class="stat-label">آجل غير محصّل</div><div class="stat-value">{{ number_format($draft['sales_credit_total'], 2) }}</div></div>
    <div class="card card-pad"><div class="stat-label">تحصيلات</div><div class="stat-value">{{ number_format($draft['collections_total'], 2) }}</div></div>
    <div class="card card-pad"><div class="stat-label">مرتجعات</div><div class="stat-value">{{ number_format($draft['returns_total'], 2) }}</div></div>
    <div class="card card-pad"><div class="stat-label">مصروفات</div><div class="stat-value">{{ number_format($draft['expenses_total'], 2) }}</div></div>
    <div class="card card-pad"><div class="stat-label">النقد المتوقع تسليمه</div><div class="stat-value" style="color: var(--info)">{{ number_format($draft['expected_cash'], 2) }}</div></div>
</div>

<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap:14px; margin-bottom:14px">
    <div class="card">
        <div class="card-pad" style="padding-bottom:8px; font-weight:600">بضاعة العهدة الحالية</div>
        @if($draft['custody_balances']->isEmpty())
            <div class="empty">لا توجد بضاعة بعهدة المندوب</div>
        @else
            <table class="table">
                <thead><tr><th>الصنف</th><th>الكمية</th><th>القيمة بالتكلفة</th></tr></thead>
                <tbody>
                @foreach($draft['custody_balances'] as $balance)
                    <tr>
                        <td>{{ $balance->product->name }}</td>
                        <td class="num">{{ $balance->qty + 0 }}</td>
                        <td class="num">{{ number_format($balance->qty * $balance->avg_cost, 2) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="card card-pad">
        <div style="font-weight:600; margin-bottom:12px">تسوية العهدة</div>
        @if($invoices->isEmpty() && $payments->isEmpty() && $expenses->isEmpty() && $draft['custody_balances']->isEmpty())
            <div class="empty">لا توجد حركات تحتاج تسوية</div>
        @else
            @if ($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif
            <form method="POST" action="{{ route('reps.settle', $rep) }}" id="settle-form">
                @csrf
                <div class="field">
                    <label for="cash_handover">النقد المُسلَّم فعليًا *</label>
                    <input class="input num" id="cash_handover" name="cash_handover" type="number" step="0.01" min="0" value="{{ $draft['expected_cash'] }}" required>
                </div>
                <div class="field">
                    <label for="main_warehouse_id">إرجاع بضاعة العهدة إلى</label>
                    <select class="input" id="main_warehouse_id" name="main_warehouse_id">
                        @foreach($main_warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field"><label for="note">ملاحظات</label><input class="input" id="note" name="note"></div>
                <button class="btn btn-primary" type="submit" onclick="event.preventDefault(); if (confirm('سيتم إرجاع بضاعة العهدة للمخزن وقفل المستندات — متابعة؟')) document.getElementById('settle-form').submit();">
                    تنفيذ التسوية
                </button>
            </form>
            <p style="color:var(--text-3); font-size:12.5px; margin-top:10px">سيتم إرجاع كل بضاعة العهدة للمخزن المحدد، وقفل فواتير وتحصيلات ومصروفات المندوب في تسوية واحدة، وقياس الفرق عن النقد المتوقع.</p>
        @endif
    </div>
</div>

<div class="card" style="margin-bottom:14px">
    <div class="card-pad" style="padding-bottom:8px; font-weight:600">فواتير غير مُسوّاة</div>
    @if($invoices->isEmpty())<div class="empty">لا يوجد</div>
    @else
        <table class="table">
            <thead><tr><th>الرقم</th><th>العميل</th><th>النوع</th><th>الإجمالي</th></tr></thead>
            <tbody>
            @foreach($invoices as $invoice)
                <tr><td><a href="{{ route('invoices.show', $invoice) }}">{{ $invoice->number }}</a></td><td>{{ $invoice->customer->name }}</td><td>@if($invoice->type==='cash')<span class="badge badge-success">نقدي</span>@else<span class="badge badge-info">آجل</span>@endif</td><td class="num">{{ number_format($invoice->total, 2) }}</td></tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
