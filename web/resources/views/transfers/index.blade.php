@extends('layouts.app')
@section('title', 'التحويلات')

@section('content')
<div class="page-header">
    <div><h1 class="page-title">التحويلات بين المخازن</h1><p class="page-sub">{{ $transfers->total() }} تحويل</p></div>
    @can('inventory.create')<div class="page-actions"><a class="btn btn-primary" href="{{ route('transfers.create') }}">تحويل جديد</a></div>@endcan
</div>

<div class="card">
    @if($transfers->isEmpty())
        <div class="empty">لا توجد تحويلات</div>
    @else
        <table class="table">
            <thead><tr><th>الرقم</th><th>من</th><th>إلى</th><th>التاريخ</th><th>عدد الأصناف</th><th></th></tr></thead>
            <tbody>
            @foreach($transfers as $transfer)
                <tr>
                    <td>{{ $transfer->number }}</td>
                    <td>{{ $transfer->fromWarehouse->name }}</td>
                    <td>{{ $transfer->toWarehouse->name }}</td>
                    <td class="num">{{ $transfer->created_at->format('Y-m-d') }}</td>
                    <td class="num">{{ $transfer->items_count ?? $transfer->items->count() }}</td>
                    <td><a class="btn btn-secondary btn-sm" href="{{ route('transfers.show', $transfer) }}">عرض</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>

{{ $transfers->links() }}
@endsection
