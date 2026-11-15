@extends('layouts.app')
@section('title', 'المخازن')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">المخازن والعُهد</h1>
        <p class="page-sub">{{ $warehouses->total() }} مخزن/عهدة — العُهد تُدار تلقائيًا مع مندوبيها</p>
    </div>
    @can('warehouses.create')<div class="page-actions"><a class="btn btn-primary" href="{{ route('warehouses.create') }}">مخزن جديد</a></div>@endcan
</div>

<div class="card">
    @if($warehouses->isEmpty())
        <div class="empty">لا توجد مخازن</div>
    @else
        <table class="table">
            <thead><tr><th>الاسم</th><th>النوع</th><th>مرتبط بـ</th><th>قيمة المخزون</th><th>الحالة</th><th></th></tr></thead>
            <tbody>
            @foreach($warehouses as $warehouse)
                @php($stockValue = $warehouse->balances()->where('qty', '!=', 0)->get()->sum(fn ($b) => $b->qty * $b->avg_cost))
                <tr>
                    <td><a href="{{ route('warehouses.show', $warehouse) }}">{{ $warehouse->name }}</a></td>
                    <td>
                        @if($warehouse->type === 'main')<span class="badge badge-info">مخزن رئيسي</span>
                        @elseif($warehouse->type === 'rep')<span class="badge badge-warning">عهدة مندوب</span>
                        @else<span class="badge badge-info">سيارة</span>@endif
                    </td>
                    <td>
                        @if($warehouse->rep)
                            <a href="{{ route('reps.show', $warehouse->rep) }}">{{ $warehouse->rep->name }}</a>
                            <span style="color:var(--text-3)">— من صفحة المندوب تتابع العهدة والتسوية</span>
                        @else
                            <span style="color:var(--text-3)">—</span>
                        @endif
                    </td>
                    <td class="num">{{ number_format($stockValue, 2) }}</td>
                    <td>@if($warehouse->is_active)<span class="badge badge-success">نشط</span>@else<span class="badge badge-error">موقوف</span>@endif</td>
                    <td>
                        @can('warehouses.update')<a class="btn btn-secondary btn-sm" href="{{ route('warehouses.edit', $warehouse) }}">تعديل</a>@endcan
                        @if($warehouse->type !== 'rep')
                            @can('warehouses.delete')
                                <form method="POST" action="{{ route('warehouses.destroy', $warehouse) }}" style="display:inline" onsubmit="return confirm('حذف المخزن؟')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-danger btn-sm" type="submit">حذف</button>
                                </form>
                            @endcan
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>

{{ $warehouses->links() }}
@endsection
