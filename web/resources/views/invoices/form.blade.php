@extends('layouts.app')
@section('title', 'فاتورة بيع جديدة')

@section('content')
<div class="page-header">
    <h1 class="page-title">فاتورة بيع جديدة</h1>
</div>

@if ($errors->any())
    <div class="alert alert-error">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ route('invoices.store') }}" id="invoice-form">
    @csrf
    <div class="card card-pad" style="margin-bottom:14px">
        <div class="form-row">
            <div class="field">
                <label for="customer_id">العميل *</label>
                <select class="input" id="customer_id" name="customer_id" required>
                    <option value="">— اختر —</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="warehouse_id">المخزن *</label>
                <select class="input" id="warehouse_id" name="warehouse_id" required>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="type">نوع البيع *</label>
                <select class="input" id="type" name="type" required>
                    <option value="cash">نقدي</option>
                    <option value="credit">آجل</option>
                </select>
            </div>
            <div class="field">
                <label for="collected_amount">المحصّل الآن (للآجل)</label>
                <input class="input num" id="collected_amount" name="collected_amount" type="number" step="0.01" min="0" value="0">
            </div>
        </div>
    </div>

    <div class="card card-pad">
        <div style="font-weight:600; margin-bottom:12px">أصناف الفاتورة</div>
        <table class="table" id="items-table">
            <thead><tr><th style="width:40%">الصنف</th><th>الكمية</th><th>السعر</th><th>الإجمالي</th><th></th></tr></thead>
            <tbody></tbody>
        </table>
        <button class="btn btn-secondary btn-sm" type="button" id="add-row" style="margin:10px 0">+ إضافة صنف</button>

        <div class="form-row" style="margin-top:10px">
            <div class="field" style="max-width:200px">
                <label for="discount">الخصم</label>
                <input class="input num" id="discount" name="discount" type="number" step="0.01" min="0" value="0">
            </div>
            <div class="field">
                <label for="note">ملاحظات</label>
                <input class="input" id="note" name="note">
            </div>
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:8px">
            <div style="font-size:16px">الإجمالي: <strong class="num" id="grand-total" style="font-size:20px">0.00</strong></div>
            <div style="display:flex; gap:8px">
                <a class="btn btn-secondary" href="{{ route('invoices.index') }}">إلغاء</a>
                <button class="btn btn-primary" type="submit">حفظ الفاتورة</button>
            </div>
        </div>
    </div>
</form>

<script>
window.productsData = @json($products);
</script>
<script src="/js/invoice-form.js"></script>
@endsection
