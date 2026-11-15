@extends('layouts.app')
@section('title', 'أعمار الديون')

@section('content')
<div class="page-header"><h1 class="page-title">مديونيات العملاء</h1></div>

<div class="card">
    @if($rows->isEmpty())
        <div class="empty">لا توجد مديونيات — ممتاز</div>
    @else
        <table class="table">
            <thead><tr><th>العميل</th><th>الرصيد المدين</th><th>أقدم فاتورة غير مسددة</th><th>عمر الدين</th></tr></thead>
            <tbody>
            @foreach($rows as $customer)
                @php($oldest = $oldestOpen[$customer->id] ?? null)
                <tr>
                    <td>{{ $customer->name }}</td>
                    <td class="num" style="color:var(--error); font-weight:600">{{ number_format($customer->balance, 2) }}</td>
                    <td class="num">{{ $oldest ? \Carbon\Carbon::parse($oldest)->format('Y-m-d') : '—' }}</td>
                    @if($oldest)
                        @php($days = $oldest->diffInDays(now()))
                        <td>@if($days > 60)<span class="badge badge-error">{{ $days }} يوم</span>@elseif($days > 30)<span class="badge badge-warning">{{ $days }} يوم</span>@else<span class="badge badge-info">{{ $days }} يوم</span>@endif</td>
                    @else
                        <td>—</td>
                    @endif
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>

{{ $rows->links() }}
@endsection
