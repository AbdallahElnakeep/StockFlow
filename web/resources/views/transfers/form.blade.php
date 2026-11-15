@extends('layouts.app')
@section('title', 'تحويل جديد')

@section('content')
<div class="page-header"><h1 class="page-title">تحويل بين مخزنين</h1></div>

@if ($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif

<form method="POST" action="{{ route('transfers.store') }}">
    @csrf
    <div class="card card-pad" style="margin-bottom:14px">
        <div class="form-row">
            <div class="field">
                <label for="from_warehouse_id">من مخزن *</label>
                <select class="input" id="from_warehouse_id" name="from_warehouse_id" required>
                    @foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>@endforeach
                </select>
            </div>
            <div class="field">
                <label for="to_warehouse_id">إلى مخزن *</label>
                <select class="input" id="to_warehouse_id" name="to_warehouse_id" required>
                    @foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>@endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="card card-pad">
        <div style="font-weight:600; margin-bottom:12px">الأصناف</div>
        <table class="table" id="items-table"><thead><tr><th style="width:40%">الصنف</th><th>الكمية</th><th></th></tr></thead><tbody></tbody></table>
        <button class="btn btn-secondary btn-sm" type="button" id="add-row" style="margin:10px 0">+ إضافة صنف</button>
        <div class="field" style="max-width:400px"><label for="note">ملاحظات</label><input class="input" id="note" name="note"></div>
        <div style="display:flex; gap:8px; justify-content:flex-end">
            <a class="btn btn-secondary" href="{{ route('transfers.index') }}">إلغاء</a>
            <button class="btn btn-primary" type="submit">تنفيذ التحويل</button>
        </div>
    </div>
</form>

<script>
window.transferProducts = @json($products);
(function () {
    var products = window.transferProducts;
    var table = document.querySelector('#items-table tbody');
    var rowCount = 0;
    document.getElementById('add-row').addEventListener('click', function () {
        rowCount++;
        var tr = document.createElement('tr');
        tr.innerHTML =
            '<td><select class="input" name="items[' + rowCount + '][product_id]" required>' +
            '<option value="">— اختر —</option>' +
            products.map(function (p) { return '<option value="' + p.id + '">' + p.name + '</option>'; }).join('') +
            '</select></td>' +
            '<td><input class="input num" name="items[' + rowCount + '][qty]" type="number" step="0.001" min="0.001" required style="width:120px"></td>' +
            '<td><button class="btn btn-danger btn-sm" type="button" onclick="this.closest(\'tr\').remove()">حذف</button></td>';
        table.appendChild(tr);
    });
    document.getElementById('add-row').click();
})();
</script>
@endsection
