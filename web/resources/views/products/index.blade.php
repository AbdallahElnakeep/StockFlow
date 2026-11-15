@extends('layouts.app')
@section('title', 'الأصناف')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">الأصناف</h1>
        <p class="page-sub">{{ $products->total() }} صنف</p>
    </div>
    @can('products.create')
        <div class="page-actions">
            <a class="btn btn-secondary" href="{{ route('categories.index') }}">التصنيفات</a>
            <a class="btn btn-secondary" href="{{ route('units.index') }}">الوحدات</a>
            <a class="btn btn-primary" href="{{ route('products.create') }}">صنف جديد</a>
        </div>
    @endcan
</div>

<form class="filter-bar" method="GET">
    <input class="input" name="q" placeholder="بحث بالاسم أو البراند..." value="{{ $q }}">
    @if(request('inactive'))
        <input type="hidden" name="inactive" value="">
    @endif
    <button class="btn btn-secondary" type="submit">بحث</button>
    <a class="btn btn-secondary" href="{{ route('products.index', request('inactive') ? [] : ['inactive' => 1]) }}">
        {{ request('inactive') ? 'الأصناف النشطة' : 'الأصناف الموقوفة' }}
    </a>
</form>

<div class="card">
    @if($products->isEmpty())
        <div class="empty">لا توجد أصناف<br><br>@can('products.create')<a class="btn btn-primary btn-sm" href="{{ route('products.create') }}">إضافة صنف</a>@endcan</div>
    @else
        <table class="table">
            <thead>
            <tr><th>الاسم</th><th>البراند</th><th>الباركود</th><th>التصنيف</th><th>التكلفة</th><th>سعر البيع</th><th>الرصيد</th><th></th></tr>
            </thead>
            <tbody>
            @foreach($products as $product)
                <tr>
                    <td>{{ $product->name }}</td>
                    <td>@if($product->brand)<span class="badge badge-info">{{ $product->brand }}</span>@else—@endif</td>
                    <td class="num">{{ $product->barcode ?? '—' }}</td>
                    <td>{{ $product->category?->name ?? '—' }}</td>
                    <td class="num">{{ number_format($product->cost_price, 2) }}</td>
                    <td class="num">{{ number_format($product->sale_price, 2) }}</td>
                    @php $qty = $stockMap[$product->id] ?? 0; @endphp
                    <td class="num" @if((float)$product->min_stock > 0 && $qty + 0 < (float)$product->min_stock) style="color: var(--warning); font-weight:600" @endif>{{ $qty + 0 }}</td>
                    <td>
                        @can('products.update')
                            <a class="btn btn-secondary btn-sm" href="{{ route('products.edit', $product) }}">تعديل</a>
                        @endcan
                        @can('products.delete')
                            @if($product->is_active)
                                <form method="POST" action="{{ route('products.destroy', $product) }}" style="display:inline" onsubmit="return confirm('إيقاف هذا الصنف؟')">
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

{{ $products->links() }}
@endsection
