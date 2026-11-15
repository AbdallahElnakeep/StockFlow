@extends('layouts.app')
@section('title', 'الموردون')

@section('content')
<div class="page-header">
    <div><h1 class="page-title">الموردون</h1><p class="page-sub">{{ $suppliers->total() }} مورد</p></div>
    @can('purchases.create')<div class="page-actions"><a class="btn btn-primary" href="{{ route('suppliers.create') }}">مورد جديد</a></div>@endcan
</div>

<form class="filter-bar" method="GET">
    <input class="input" name="q" placeholder="اسم المورد..." value="{{ $q }}">
    <button class="btn btn-secondary" type="submit">بحث</button>
</form>

<div class="card">
    @if($suppliers->isEmpty())
        <div class="empty">لا يوجد موردون</div>
    @else
        <table class="table">
            <thead><tr><th>الاسم</th><th>الجوال</th><th>المستحق له</th><th>الحالة</th><th></th></tr></thead>
            <tbody>
            @foreach($suppliers as $supplier)
                <tr>
                    <td>{{ $supplier->name }}</td>
                    <td class="num">{{ $supplier->phone ?? '—' }}</td>
                    <td class="num" @if($supplier->balance > 0) style="color:var(--error); font-weight:600" @endif>{{ number_format($supplier->balance, 2) }}</td>
                    <td>@if($supplier->is_active)<span class="badge badge-success">نشط</span>@else<span class="badge badge-error">موقوف</span>@endif</td>
                    <td>
                        @can('purchases.update')<a class="btn btn-secondary btn-sm" href="{{ route('suppliers.edit', $supplier) }}">تعديل</a>@endcan
                        @can('purchases.delete')
                            <form method="POST" action="{{ route('suppliers.destroy', $supplier) }}" style="display:inline" onsubmit="return confirm('حذف المورد؟')">
                                @csrf @method('DELETE')
                                <button class="btn btn-danger btn-sm" type="submit">حذف</button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>

{{ $suppliers->links() }}
@endsection
