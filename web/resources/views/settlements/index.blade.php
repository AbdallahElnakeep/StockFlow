@extends('layouts.app')
@section('title', 'تسويات العُهد')

@section('content')
<div class="page-header">
    <div><h1 class="page-title">تسويات العُهد</h1><p class="page-sub">{{ $settlements->total() }} تسوية</p></div>
</div>

<div class="card">
    @if($settlements->isEmpty())
        <div class="empty">لا توجد تسويات بعد — تُنشأ التسوية من صفحة المندوب</div>
    @else
        <table class="table">
            <thead><tr><th>الرقم</th><th>المندوب</th><th>التاريخ</th><th>النقد المتوقع</th><th>المُسلَّم</th><th>الفرق</th><th>الحالة</th></tr></thead>
            <tbody>
            @foreach($settlements as $settlement)
                <tr>
                    <td><a href="{{ route('settlements.show', $settlement) }}">{{ $settlement->number }}</a></td>
                    <td>{{ $settlement->rep->name }}</td>
                    <td class="num">{{ $settlement->settlement_date->format('Y-m-d') }}</td>
                    <td class="num">{{ number_format($settlement->sales_cash_total + $settlement->collections_total - $settlement->returns_total - $settlement->expenses_total, 2) }}</td>
                    <td class="num">{{ number_format($settlement->cash_handover, 2) }}</td>
                    <td class="num" @if($settlement->difference < 0) style="color:var(--error); font-weight:600" @endif>{{ number_format($settlement->difference, 2) }}</td>
                    <td><span class="badge badge-success">مُسوّاة</span></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>

{{ $settlements->links() }}
@endsection
