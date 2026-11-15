@extends('layouts.app')
@section('title', 'مرتجع جديد')

@section('content')
<div class="page-header"><h1 class="page-title">مرتجع بيع جديد</h1></div>

@if ($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif

<form method="POST" action="{{ route('returns.store') }}">
    @csrf
    <div class="card card-pad" style="margin-bottom:14px">
        <div class="form-row">
            <div class="field">
                <label for="customer_id">العميل *</label>
                <select class="input" id="customer_id" name="customer_id" required>
                    <option value="">— اختر —</option>
                    @foreach($customers as $customer)<option value="{{ $customer->id }}">{{ $customer->name }}</option>@endforeach
                </select>
            </div>
            <div class="field">
                <label for="warehouse_id">المخزن *</label>
                <select class="input" id="warehouse_id" name="warehouse_id" required>
                    @foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>@endforeach
                </select>
            </div>
            <div class="field">
                <label for="restock">مصير البضاعة *</label>
                <select class="input" id="restock" name="restock" required>
                    <option value="1">إعادة للمخزون (صالح)</option>
                    <option value="0">تالف (لا يُخزَّن)</option>
                </select>
            </div>
        </div>
    </div>

    <div class="card card-pad">
        <div style="font-weight:600; margin-bottom:12px">أصناف المرتجع</div>
        <table class="table" id="items-table"><thead><tr><th style="width:40%">الصنف</th><th>الكمية</th><th>السعر</th><th>الإجمالي</th><th></th></tr></thead><tbody></tbody></table>
        <button class="btn btn-secondary btn-sm" type="button" id="add-row" style="margin:10px 0">+ إضافة صنف</button>
        <div class="field" style="max-width:400px"><label for="note">ملاحظات</label><input class="input" id="note" name="note"></div>
        <div style="display:flex; gap:8px; justify-content:flex-end">
            <a class="btn btn-secondary" href="{{ route('returns.index') }}">إلغاء</a>
            <button class="btn btn-primary" type="submit">حفظ المرتجع</button>
        </div>
    </div>
</form>

<script>
window.returnProducts = @json($products);
(function () {
    var products = window.returnProducts;
    var table = document.querySelector('#items-table tbody');
    var rowCount = 0;
    function recalc() {
        table.querySelectorAll('tr').forEach(function (row) {
            var qty = parseFloat(row.querySelector('.item-qty').value) || 0;
            var price = parseFloat(row.querySelector('.item-price').value) || 0;
            row.querySelector('.item-total').textContent = (qty * price).toFixed(2);
        });
    }
    document.getElementById('add-row').addEventListener('click', function () {
        rowCount++;
        var tr = document.createElement('tr');
        tr.innerHTML =
            '<td><select class="input item-product" name="items[' + rowCount + '][product_id]" required>' +
            '<option value="">— اختر —</option>' +
            products.map(function (p) { return '<option value="' + p.id + '" data-price="' + p.sale_price + '">' + p.name + '</option>'; }).join('') +
            '</select></td>' +
            '<td><input class="input item-qty num" name="items[' + rowCount + '][qty]" type="number" step="0.001" min="0.001" required style="width:100px"></td>' +
            '<td><input class="input item-price num" name="items[' + rowCount + '][unit_price]" type="number" step="0.01" min="0" required style="width:110px"></td>' +
            '<td class="num item-total">0.00</td>' +
            '<td><button class="btn btn-danger btn-sm" type="button" onclick="this.closest(\'tr\').remove()">حذف</button></td>';
        table.appendChild(tr);
        tr.querySelector('.item-product').addEventListener('change', function () {
            tr.querySelector('.item-price').value = this.selectedOptions[0].dataset.price || 0;
            recalc();
        });
        tr.querySelector('.item-qty').addEventListener('input', recalc);
        tr.querySelector('.item-price').addEventListener('input', recalc);
    });
    document.getElementById('add-row').click();
})();
</script>
@endsection
