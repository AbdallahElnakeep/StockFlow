@extends('layouts.app')
@section('title', 'تقرير المندوبين')

@section('content')
<div class="page-header"><h1 class="page-title">أداء المندوبين</h1></div>

<form class="filter-bar" method="GET">
    <input class="input" name="from" type="date" value="{{ $from }}">
    <input class="input" name="to" type="date" value="{{ $to }}">
    <button class="btn btn-secondary" type="submit">عرض</button>
</form>

<div class="card">
    @if($rows->isEmpty())
        <div class="empty">لا يوجد مندوبون</div>
    @else
        <table class="table">
            <thead><tr><th>المندوب</th><th>عدد الفواتير</th><th>المبيعات</th><th>التحصيلات</th><th>المصروفات</th><th>صافي النقد</th></tr></thead>
            <tbody>
            @foreach($rows as $rep)
                @php($net = ($rep->collections_total ?? 0) - ($rep->expenses_total ?? 0))
                <tr>
                    <td>{{ $rep->name }}</td>
                    <td class="num">{{ $rep->invoices_count ?? 0 }}</td>
                    <td class="num">{{ number_format($rep->sales_total ?? 0, 2) }}</td>
                    <td class="num">{{ number_format($rep->collections_total ?? 0, 2) }}</td>
                    <td class="num">{{ number_format($rep->expenses_total ?? 0, 2) }}</td>
                    <td class="num" @if($net < 0) style="color:var(--error)" @endif>{{ number_format($net, 2) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
