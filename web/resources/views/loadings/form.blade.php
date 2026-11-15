@extends('layouts.app')
@section('title', 'تحميل عهدة جديدة')

@section('content')
<div class="page-header"><h1 class="page-title">تحميل عهدة مندوب</h1></div>

@if ($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif

<form method="POST" action="{{ route('loadings.store') }}">
    @csrf
    <div class="card card-pad" style="margin-bottom:14px">
        <div class="form-row">
            <div class="field">
                <label for="rep_user_id">المندوب *</label>
                <select class="input" id="rep_user_id" name="rep_user_id" required>
                    <option value="">— اختر —</option>
                    @foreach($reps as $rep)<option value="{{ $rep->id }}">{{ $rep->name }}</option>@endforeach
                </select>
            </div>
            <div class="field">
                <label for="from_warehouse_id">من مخزن *</label>
                <select class="input" id="from_warehouse_id" name="from_warehouse_id" required>
                    @foreach(\App\Models\Warehouse::where('type', 'main')->orderBy('name')->get() as $warehouse)
                        <option value="{{ $warehouse->id }}" {{ $warehouse->id == $from_warehouse_id ? 'selected' : '' }}>{{ $warehouse->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="card card-pad">
        <div style="font-weight:600; margin-bottom:12px">الأصناف المحمّلة</div>
        <table class="table" id="items-table"><thead><tr><th style="width:40%">الصنف</th><th>الرصيد المتاح</th><th>الكمية</th><th></th></tr></thead><tbody></tbody></table>
        <button class="btn btn-secondary btn-sm" type="button" id="add-row" style="margin:10px 0">+ إضافة صنف</button>
        <div class="field" style="max-width:400px; margin-top:10px"><label for="note">ملاحظات</label><input class="input" id="note" name="note"></div>
        <div style="display:flex; gap:8px; justify-content:flex-end">
            <a class="btn btn-secondary" href="{{ route('loadings.index') }}">إلغاء</a>
            <button class="btn btn-primary" type="submit">تنفيذ التحميل</button>
        </div>
    </div>
</form>

<script>
window.loadingProducts = @json($products);
window.loadingStock = @json(collect($stockMap ?? collect())->map(fn($b) => $b->qty + 0));
</script>
<script>
(function () {
    var products = window.loadingProducts || [];
    var stock = window.loadingStock || {};
    var table = document.querySelector('#items-table tbody');
    var rowCount = 0;

    document.getElementById('add-row').addEventListener('click', function () {
        rowCount++;
        var tr = document.createElement('tr');
        tr.innerHTML =
            '<td><select class="input item-product" name="items[' + rowCount + '][product_id]" required>' +
            '<option value="">— اختر الصنف —</option>' +
            products.map(function (p) { return '<option value="' + p.id + '" data-stock="' + (stock[p.id] || 0) + '">' + p.name + '</option>'; }).join('') +
            '</select></td>' +
            '<td class="num item-stock">—</td>' +
            '<td><input class="input item-qty num" name="items[' + rowCount + '][qty]" type="number" step="0.001" min="0.001" required style="width:110px"></td>' +
            '<td><button class="btn btn-danger btn-sm" type="button" onclick="this.closest(\'tr\').remove()">حذف</button></td>';
        table.appendChild(tr);
        var sel = tr.querySelector('.item-product');
        sel.addEventListener('change', function () {
            tr.querySelector('.item-stock').textContent = this.selectedOptions[0].dataset.stock;
        });
    });

    document.getElementById('add-row').click();
})();
</script>
@endsection
