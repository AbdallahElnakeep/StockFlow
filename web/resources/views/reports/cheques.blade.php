@extends('layouts.app')
@section('title', 'الشيكات')

@section('content')
<div class="page-header"><h1 class="page-title">الشيكات</h1></div>

<div class="card">
    @if($cheques->isEmpty())
        <div class="empty">لا توجد شيكات مسجلة</div>
    @else
        <table class="table">
            <thead><tr><th>الرقم</th><th>الحساب</th><th>رقم الشيك</th><th>الاستحقاق</th><th>المتبقي</th><th>الحالة</th></tr></thead>
            <tbody>
            @foreach($cheques as $cheque)
                @php($days = $cheque->due_date->diffInDays(now(), false))
                <tr>
                    <td>{{ $cheque->number }}</td>
                    <td>{{ $cheque->payable?->name ?? '—' }}</td>
                    <td class="num">{{ $cheque->cheque_no }}</td>
                    <td class="num">{{ $cheque->due_date->format('Y-m-d') }}</td>
                    <td class="num">{{ number_format($cheque->amount, 2) }}</td>
                    <td>
                        @if($days < 0)<span class="badge badge-info">بعد {{ abs($days) }} يوم</span>
                        @elseif($days == 0)<span class="badge badge-warning">مستحق اليوم</span>
                        @else<span class="badge badge-{{ $days > 7 ? 'error' : 'warning' }}">متأخر {{ $days }} يوم</span>@endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
