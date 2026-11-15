@extends('layouts.app')
@section('title', 'تحميل العهد')

@section('content')
<div class="page-header">
    <div><h1 class="page-title">تحميل العُهد</h1><p class="page-sub">{{ $loadings->total() }} عملية تحميل</p></div>
    @can('reps.create')<div class="page-actions"><a class="btn btn-primary" href="{{ route('loadings.create') }}">تحميل جديد</a></div>@endcan
</div>

<div class="card">
    @if($loadings->isEmpty())
        <div class="empty">لا توجد عمليات تحميل<br><br>@can('reps.create')<a class="btn btn-primary btn-sm" href="{{ route('loadings.create') }}">تحميل عهدة مندوب</a>@endcan</div>
    @else
        <table class="table">
            <thead><tr><th>الرقم</th><th>المندوب</th><th>من مخزن</th><th>التاريخ</th><th>قيمة العهدة</th></tr></thead>
            <tbody>
            @foreach($loadings as $loading)
                <tr>
                    <td><a href="{{ route('loadings.show', $loading) }}">{{ $loading->number }}</a></td>
                    <td>{{ $loading->rep->name }}</td>
                    <td>{{ $loading->fromWarehouse->name }}</td>
                    <td class="num">{{ $loading->created_at->format('Y-m-d') }}</td>
                    <td class="num">{{ number_format($loading->total_cost, 2) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>

{{ $loadings->links() }}
@endsection
