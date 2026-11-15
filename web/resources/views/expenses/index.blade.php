@extends('layouts.app')
@section('title', 'المصروفات')

@section('content')
<div class="page-header">
    <div><h1 class="page-title">مصروفات التوزيع</h1><p class="page-sub">{{ $expenses->total() }} مصروف</p></div>
    @can('expenses.create')<div class="page-actions"><a class="btn btn-primary" href="{{ route('expenses.create') }}">مصروف جديد</a></div>@endcan
</div>

<form class="filter-bar" method="GET">
    <select class="input" name="category">
        <option value="">كل التصنيفات</option>
        @foreach($categories as $category)
            <option value="{{ $category }}" {{ $category === request('category') ? 'selected' : '' }}>{{ $category }}</option>
        @endforeach
    </select>
    <button class="btn btn-secondary" type="submit">تصفية</button>
</form>

<div class="card">
    @if($expenses->isEmpty())
        <div class="empty">لا توجد مصروفات</div>
    @else
        <table class="table">
            <thead><tr><th>التاريخ</th><th>التصنيف</th><th>المندوب</th><th>القيمة</th><th>ملاحظة</th></tr></thead>
            <tbody>
            @foreach($expenses as $expense)
                <tr>
                    <td class="num">{{ $expense->issued_at->format('Y-m-d') }}</td>
                    <td><span class="badge badge-info">{{ $expense->category }}</span></td>
                    <td>{{ $expense->rep?->name ?? 'الإدارة' }}</td>
                    <td class="num">{{ number_format($expense->amount, 2) }}</td>
                    <td>{{ $expense->note ?? '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>

{{ $expenses->links() }}
@endsection
