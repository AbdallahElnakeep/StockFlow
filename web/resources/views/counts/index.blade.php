@extends('layouts.app')
@section('title', 'الجرد')

@section('content')
<div class="page-header">
    <div><h1 class="page-title">الجرد والتسويات</h1><p class="page-sub">{{ $counts->total() }} عملية جرد</p></div>
    @can('inventory.create')<div class="page-actions"><a class="btn btn-primary" href="{{ route('counts.create') }}">جرد جديد</a></div>@endcan
</div>

<div class="card">
    @if($counts->isEmpty())
        <div class="empty">لا توجد عمليات جرد</div>
    @else
        <table class="table">
            <thead><tr><th>الرقم</th><th>المخزن</th><th>التاريخ</th><th>الحالة</th><th></th></tr></thead>
            <tbody>
            @foreach($counts as $count)
                <tr>
                    <td>{{ $count->number }}</td>
                    <td>{{ $count->warehouse->name }}</td>
                    <td class="num">{{ $count->created_at->format('Y-m-d') }}</td>
                    <td>@if($count->status === 'draft')<span class="badge badge-warning">جاري</span>@else<span class="badge badge-success">مُسوّى</span>@endif</td>
                    <td><a class="btn btn-secondary btn-sm" href="{{ route('counts.show', $count) }}">فتح</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>

{{ $counts->links() }}
@endsection
