@extends('layouts.app')
@section('title', 'المرتجعات')

@section('content')
<div class="page-header">
    <div><h1 class="page-title">مرتجعات البيع</h1><p class="page-sub">{{ $returns->total() }} مرتجع</p></div>
    @can('returns.create')<div class="page-actions"><a class="btn btn-primary" href="{{ route('returns.create') }}">مرتجع جديد</a></div>@endcan
</div>

<form class="filter-bar" method="GET">
    <input class="input" name="q" placeholder="رقم المرتجع..." value="{{ $q }}">
    <button class="btn btn-secondary" type="submit">بحث</button>
</form>

<div class="card">
    @if($returns->isEmpty())
        <div class="empty">لا توجد مرتجعات</div>
    @else
        <table class="table">
            <thead><tr><th>الرقم</th><th>العميل</th><th>المصدر</th><th>إعادة للمخزن</th><th>الإجمالي</th><th>التاريخ</th></tr></thead>
            <tbody>
            @foreach($returns as $return)
                <tr>
                    <td>{{ $return->number }}</td>
                    <td>{{ $return->customer->name }}</td>
                    <td>{{ $return->rep?->name ?? 'الإدارة' }}</td>
                    <td>@if($return->restock)<span class="badge badge-success">نعم</span>@else<span class="badge badge-error">تالف</span>@endif</td>
                    <td class="num">{{ number_format($return->total, 2) }}</td>
                    <td class="num">{{ $return->issued_at->format('Y-m-d') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>

{{ $returns->links() }}
@endsection
