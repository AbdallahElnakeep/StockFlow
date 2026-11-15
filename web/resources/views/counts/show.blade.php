@extends('layouts.app')
@section('title', 'جرد '.$count->number)

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">جرد {{ $count->number }}</h1>
        <p class="page-sub">{{ $count->warehouse->name }} · {{ $count->status === 'draft' ? 'جارٍ الجرد' : 'تمت التسوية' }}</p>
    </div>
    @if($count->status === 'draft')
        @can('inventory.update')
            <form method="POST" action="{{ route('counts.adjust', $count) }}" onsubmit="return confirm('سيتم تعديل أرصدة المخزن حسب الفروقات — متابعة؟')">
                @csrf
                <button class="btn btn-primary" type="submit">تنفيذ التسوية</button>
            </form>
        @endcan
    @endif
</div>

@if ($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif

<form method="POST" action="{{ route('counts.update', $count) }}">
    @csrf @method('PUT')
    <div class="card">
        @if($count->items->isEmpty())
            <div class="empty">لا توجد أصناف في هذا المخزن</div>
        @else
            <table class="table">
                <thead><tr><th>الصنف</th><th>رصيد النظام</th><th>الكمية الفعلية</th><th>الفرق</th></tr></thead>
                <tbody>
                @foreach($count->items as $item)
                    <tr>
                        <td>{{ $item->product->name }}</td>
                        <td class="num">{{ $item->system_qty + 0 }}</td>
                        @if($count->status === 'draft')
                            <td><input class="input num" name="items[{{ $item->product_id }}][counted_qty]" type="number" step="0.001" min="0" value="{{ $item->counted_qty }}" placeholder="لم يُجرد" style="width:120px"></td>
                        @else
                            <td class="num">{{ $item->counted_qty + 0 }}</td>
                        @endif
                        @php($diff = $item->difference + 0)
                        <td class="num" @if($diff != 0) style="color: var(--error); font-weight:600" @endif>{{ $diff != 0 ? number_format($diff, 3) : '—' }}</td>
                        <input type="hidden" name="items[{{ $item->product_id }}][product_id]" value="{{ $item->product_id }}">
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
    @if($count->status === 'draft')
        @can('inventory.update')
            <div style="margin-top:12px"><button class="btn btn-secondary" type="submit">حفظ الكميات</button></div>
        @endcan
    @endif
</form>
@endsection
