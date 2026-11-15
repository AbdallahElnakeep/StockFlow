@extends('layouts.app')
@section('title', 'التصنيفات')

@section('content')
<div class="page-header">
    <div><h1 class="page-title">تصنيفات الأصناف</h1></div>
    @can('products.create')<div class="page-actions"><a class="btn btn-primary" href="{{ route('categories.create') }}">تصنيف جديد</a></div>@endcan
</div>

<div class="card">
    @if($categories->isEmpty())
        <div class="empty">لا توجد تصنيفات</div>
    @else
        <table class="table">
            <thead><tr><th>الاسم</th><th>تصنيف أب</th><th></th></tr></thead>
            <tbody>
            @foreach($categories as $category)
                <tr>
                    <td>{{ $category->name }}</td>
                    <td>{{ $category->parent?->name ?? '—' }}</td>
                    <td>
                        @can('products.update')<a class="btn btn-secondary btn-sm" href="{{ route('categories.edit', $category) }}">تعديل</a>@endcan
                        @can('products.delete')
                            <form method="POST" action="{{ route('categories.destroy', $category) }}" style="display:inline" onsubmit="return confirm('حذف التصنيف؟')">
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
