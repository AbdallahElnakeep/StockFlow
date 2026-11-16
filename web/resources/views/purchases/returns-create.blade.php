@extends('layouts.app')
@section('title', 'مرتجع مشتريات جديد')

@section('content')
<div class="page-header">
    <div><h1 class="page-title">مرتجع مشتريات جديد</h1></div>
</div>

<form method="POST" action="{{ route('purchase-returns.store') }}">
    @csrf
    <div class="card" style="padding:16px; display:grid; gap:12px">
        @if ($errors->any())
            <div class="alert alert-error">{{ $errors->first() }}</div>
        @endif

        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:12px">
            <div>
                <label class="label">المورد *</label>
                <select class="input" name="supplier_id" required onchange="window.location='{{ route('purchase-returns.create') }}?supplier_id='+this.value">
                    <option value="">اختر المورد</option>
                    @foreach($suppliers as $s)
                        <option value="{{ $s->id }}" {{ $supplierId === $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">المخزن (الصادر منه) *</label>
                <select class="input" name="warehouse_id" required>
                    @foreach($warehouses as $w)
                        <option value="{{ $w->id }}">{{ $w->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">نوع المرتجع *</label>
                <select class="input" name="type" required>
                    <option value="supplier">مرتجع للمورد (خصم من رصيده)</option>
                    <option value="damage">إهلاك تالف (تحميل على الهالك)</option>
                </select>
            </div>
            <div>
                <label class="label">ملاحظة</label>
                <input class="input" type="text" name="note" maxlength="500" value="{{ old('note') }}">
            </div>
        </div>

        @if($supplierId)
            <div>
                <label class="label">الأصناف (الصالح للإرجاع يظهر بجانب كل صنف)</label>
                <table class="table">
                    <thead><tr><th>الصنف</th><th>الصالح للإرجاع</th><th>الكمية المرتجعة</th></tr></thead>
                    <tbody>
                    @foreach($products as $p)
                        <tr>
                            <td>{{ $p->name }}<input type="hidden" name="items[{{ $p->id }}][product_id]" value="{{ $p->id }}"></td>
                            <td class="num">{{ $costs[$p->id] + 0 }}</td>
                            <td class="num"><input class="input" type="number" step="0.001" min="0" max="{{ $costs[$p->id] }}" name="items[{{ $p->id }}][qty]" value="0" style="max-width:120px"></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                <input type="hidden" name="items_js" value="">
            </div>
            <button class="btn btn-primary" type="submit" style="justify-self:start">تسجيل المرتجع</button>
        @else
            <div class="empty">اختر المورد أولًا لعرض أصنافه القابلة للإرجاع</div>
        @endif
    </div>
</form>

<script>
    document.querySelector('form').addEventListener('submit', function (e) {
        var any = Array.prototype.some.call(document.querySelectorAll('input[name$="[qty]"]'), function (i) { return parseFloat(i.value) > 0; });
        if (!any) { e.preventDefault(); alert('أدخل كمية مرتجعة لصنف واحد على الأقل'); }
    });
</script>
@endsection
