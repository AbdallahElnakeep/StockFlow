@extends('layouts.app')
@section('title', $product->exists ? 'تعديل صنف' : 'صنف جديد')

@section('content')
<div class="page-header">
    <h1 class="page-title">{{ $product->exists ? 'تعديل: '.$product->name : 'صنف جديد' }}</h1>
</div>

<div class="card card-pad" style="max-width: 640px">
    @if ($errors->any())
        <div class="alert alert-error">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ $product->exists ? route('products.update', $product) : route('products.store') }}">
        @csrf
        @if($product->exists) @method('PUT') @endif

        <div class="field">
            <label for="name">اسم الصنف *</label>
            <input class="input {{ $errors->has('name') ? 'input-error' : '' }}" id="name" name="name" value="{{ old('name', $product->name) }}" required>
        </div>

        <div class="form-row">
            <div class="field">
                <label for="category_id">التصنيف</label>
                <select class="input" id="category_id" name="category_id">
                    <option value="">— بدون —</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="unit_id">الوحدة</label>
                <select class="input" id="unit_id" name="unit_id">
                    <option value="">— بدون —</option>
                    @foreach($units as $unit)
                        <option value="{{ $unit->id }}" {{ old('unit_id', $product->unit_id) == $unit->id ? 'selected' : '' }}>{{ $unit->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="field">
            <label for="brand">البراند</label>
            <input class="input" id="brand" name="brand" value="{{ old('brand', $product->brand) }}" placeholder="مثال: النخبة">
        </div>

        <div class="form-row">
            <div class="field">
                <label for="barcode">الباركود</label>
                <input class="input" id="barcode" name="barcode" value="{{ old('barcode', $product->barcode) }}">
            </div>
            <div class="field">
                <label for="sku">الكود الداخلي</label>
                <input class="input" id="sku" name="sku" value="{{ old('sku', $product->sku) }}">
            </div>
        </div>

        <div class="form-row">
            <div class="field">
                <label for="cost_price">سعر التكلفة *</label>
                <input class="input num" id="cost_price" name="cost_price" type="number" step="0.01" min="0" value="{{ old('cost_price', $product->cost_price ?? 0) }}" required>
            </div>
            <div class="field">
                <label for="sale_price">سعر البيع *</label>
                <input class="input num" id="sale_price" name="sale_price" type="number" step="0.01" min="0" value="{{ old('sale_price', $product->sale_price ?? 0) }}" required>
            </div>
            <div class="field">
                <label for="min_stock">الحد الأدنى للمخزون</label>
                <input class="input num" id="min_stock" name="min_stock" type="number" step="0.01" min="0" value="{{ old('min_stock', $product->min_stock ?? 0) }}">
            </div>
        </div>

        <div style="display:flex; gap:8px">
            <button class="btn btn-primary" type="submit">حفظ</button>
            <a class="btn btn-secondary" href="{{ route('products.index') }}">إلغاء</a>
        </div>
    </form>
</div>
@endsection
