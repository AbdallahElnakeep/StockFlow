@extends('layouts.app')
@section('title', 'التحصيلات')

@section('content')
<div class="page-header">
    <div><h1 class="page-title">التحصيلات والمدفوعات</h1><p class="page-sub">{{ $payments->total() }} حركة</p></div>
    @can('payments.create')
        <div class="page-actions">
            <a class="btn btn-primary" href="{{ route('payments.create', ['direction' => 'in']) }}">تحصيل من عميل</a>
            <a class="btn btn-secondary" href="{{ route('payments.create', ['direction' => 'out']) }}">سداد لمورد</a>
        </div>
    @endcan
</div>

<form class="filter-bar" method="GET">
    <select class="input" name="direction">
        <option value="">الكل</option>
        <option value="in" {{ $direction === 'in' ? 'selected' : '' }}>تحصيلات</option>
        <option value="out" {{ $direction === 'out' ? 'selected' : '' }}>مدفوعات</option>
    </select>
    <button class="btn btn-secondary" type="submit">تصفية</button>
</form>

<div class="card">
    @if($payments->isEmpty())
        <div class="empty">لا توجد حركات</div>
    @else
        <table class="table">
            <thead><tr><th>الرقم</th><th>الحساب</th><th>النوع</th><th>المندوب</th><th>القيمة</th><th>التاريخ</th></tr></thead>
            <tbody>
            @foreach($payments as $payment)
                <tr>
                    <td>{{ $payment->number }}</td>
                    <td>{{ $payment->payable?->name ?? '—' }}</td>
                    <td>@if($payment->direction === 'in')<span class="badge badge-success">تحصيل</span>@else<span class="badge badge-info">سداد</span>@endif</td>
                    <td>{{ $payment->rep?->name ?? '—' }}</td>
                    <td class="num">{{ number_format($payment->amount, 2) }}</td>
                    <td class="num">{{ $payment->issued_at->format('Y-m-d') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>

{{ $payments->links() }}
@endsection
