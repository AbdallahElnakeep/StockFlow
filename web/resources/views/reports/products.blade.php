@extends('layouts.app')
@section('title', 'تقرير الأصناف')

@section('content')
<div class="page-header"><button type="button" class="btn btn-secondary btn-sm export-pdf" onclick="window.print()">تصدير PDF</button><h1 class="page-title">الأصناف الأكثر مبيعًا</h1></div>

<form class="filter-bar" method="GET">
    <input class="input" name="from" type="date" value="{{ $from }}">
    <input class="input" name="to" type="date" value="{{ $to }}">
    <button class="btn btn-secondary" type="submit">عرض</button>
</form>

<div class="card">
    @if($rows->isEmpty())
        <div class="empty">لا توجد مبيعات في هذه الفترة</div>
    @else
        <table class="table">
            <thead><tr><th>الصنف</th><th>الكمية المبيعة</th><th>الإيراد</th><th>التكلفة</th><th>هامش الربح</th></tr></thead>
            <tbody>
            @foreach($rows as $row)
                <tr>
                    <td>{{ $row->name }}</td>
                    <td class="num">{{ $row->qty + 0 }}</td>
                    <td class="num">{{ number_format($row->revenue, 2) }}</td>
                    <td class="num">{{ number_format($row->cost, 2) }}</td>
                    <td class="num" style="color:var(--success)">{{ number_format($row->revenue - $row->cost, 2) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>

{{ $rows->links() }}
@endsection
