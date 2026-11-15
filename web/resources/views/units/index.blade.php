@extends('layouts.app')
@section('title', 'الوحدات')

@section('content')
<div class="page-header">
    <div><h1 class="page-title">وحدات القياس</h1></div>
    @can('products.create')<div class="page-actions"><a class="btn btn-primary" href="{{ route('units.create') }}">وحدة جديدة</a></div>@endcan
</div>

<div class="card">
    @if($units->isEmpty())
        <div class="empty">لا توجد وحدات</div>
    @else
        <table class="table">
            <thead><tr><th>الاسم</th><th>الاختصار</th><th>عدد الأصناف</th><th></th></tr></thead>
            <tbody>
            @foreach($units as $unit)
                <tr>
                    <td>{{ $unit->name }}</td>
                    <td>{{ $unit->short_name }}</td>
                    <td class="num">{{ $unit->products()->count() }}</td>
                    <td>
                        @can('products.update')<a class="btn btn-secondary btn-sm" href="{{ route('units.edit', $unit) }}">تعديل</a>@endcan
                        @can('products.delete')
                            <form method="POST" action="{{ route('units.destroy', $unit) }}" style="display:inline" onsubmit="return confirm('حذف الوحدة؟')">
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
@endsection
