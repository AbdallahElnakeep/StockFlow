@extends('layouts.app')
@section('title', 'العملاء')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">العملاء</h1>
        <p class="page-sub">{{ $customers->total() }} عميل</p>
    </div>
    @can('customers.create')
        <div class="page-actions"><a class="btn btn-primary" href="{{ route('customers.create') }}">عميل جديد</a></div>
    @endcan
</div>

<form class="filter-bar" method="GET">
    <input class="input" name="q" placeholder="بحث بالاسم أو الجوال..." value="{{ $q }}">
    <select class="input" name="area">
        <option value="">كل المناطق</option>
        @foreach($areas as $a)
            <option value="{{ $a }}" {{ $area === $a ? 'selected' : '' }}>{{ $a }}</option>
        @endforeach
    </select>
    <label style="display:flex; align-items:center; gap:6px; font-size:13px; color:var(--text-2)">
        <input type="checkbox" name="debtors" value="1" {{ request('debtors') ? 'checked' : '' }}> المدينون فقط
    </label>
    <button class="btn btn-secondary" type="submit">تصفية</button>
</form>

<div class="card">
    @if($customers->isEmpty())
        <div class="empty">لا يوجد عملاء<br><br>@can('customers.create')<a class="btn btn-primary btn-sm" href="{{ route('customers.create') }}">إضافة عميل</a>@endcan</div>
    @else
        <table class="table">
            <thead><tr><th>الاسم</th><th>الجوال</th><th>المنطقة</th><th>الرصيد</th><th>حد الآجل</th><th></th></tr></thead>
            <tbody>
            @foreach($customers as $customer)
                <tr>
                    <td>{{ $customer->name }}</td>
                    <td class="num">{{ $customer->phone ?? '—' }}</td>
                    <td>{{ $customer->area ?? '—' }}</td>
                    <td class="num" @if($customer->balance > 0) style="color: var(--error); font-weight:600" @endif><a href="{{ route('reports.statement', $customer) }}">{{ number_format($customer->balance, 2) }}</a></td>
                    <td class="num">{{ number_format($customer->credit_limit, 2) }}</td>
                    <td>
                        @can('reports.view')<a class="btn btn-secondary btn-sm" href="{{ route('reports.statement', $customer) }}">كشف حساب</a>@endcan
                        @can('customers.update')<a class="btn btn-secondary btn-sm" href="{{ route('customers.edit', $customer) }}">تعديل</a>@endcan
                        @can('customers.delete')
                            @if($customer->is_active)
                                <form method="POST" action="{{ route('customers.destroy', $customer) }}" style="display:inline" onsubmit="return confirm('إيقاف هذا العميل؟')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-danger btn-sm" type="submit">إيقاف</button>
                                </form>
                            @endif
                        @endcan
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>

{{ $customers->links() }}
@endsection
