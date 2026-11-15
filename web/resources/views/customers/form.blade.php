@extends('layouts.app')
@section('title', $customer->exists ? 'تعديل عميل' : 'عميل جديد')

@section('content')
<div class="page-header">
    <h1 class="page-title">{{ $customer->exists ? 'تعديل: '.$customer->name : 'عميل جديد' }}</h1>
</div>

<div class="card card-pad" style="max-width: 640px">
    @if ($errors->any())
        <div class="alert alert-error">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ $customer->exists ? route('customers.update', $customer) : route('customers.store') }}">
        @csrf
        @if($customer->exists) @method('PUT') @endif

        <div class="field">
            <label for="name">اسم العميل *</label>
            <input class="input {{ $errors->has('name') ? 'input-error' : '' }}" id="name" name="name" value="{{ old('name', $customer->name) }}" required>
        </div>

        <div class="form-row">
            <div class="field">
                <label for="phone">الجوال</label>
                <input class="input" id="phone" name="phone" value="{{ old('phone', $customer->phone) }}">
            </div>
            <div class="field">
                <label for="area">المنطقة</label>
                <input class="input" id="area" name="area" value="{{ old('area', $customer->area) }}">
            </div>
        </div>

        <div class="field">
            <label for="address">العنوان</label>
            <textarea class="input" id="address" name="address" rows="2">{{ old('address', $customer->address) }}</textarea>
        </div>

        <div class="form-row">
        <div class="field" style="max-width:220px">
            <label for="opening_balance">رصيد افتتاحي (مديونية سابقة)</label>
            @if($customer->exists)<input class="input num" value="—" disabled><input type="hidden" name="opening_balance" value="0">
            @else<input class="input num" id="opening_balance" name="opening_balance" type="number" step="0.01" min="0" value="0">@endif
        </div>
        <div class="field" style="max-width:220px">
            <label for="credit_limit">حد البيع الآجل (0 = نقدي فقط)</label>
            <input class="input num" id="credit_limit" name="credit_limit" type="number" step="0.01" min="0" value="{{ old('credit_limit', $customer->credit_limit ?? 0) }}">
        </div>
        </div>

        <div style="display:flex; gap:8px">
            <button class="btn btn-primary" type="submit">حفظ</button>
            <a class="btn btn-secondary" href="{{ route('customers.index') }}">إلغاء</a>
        </div>
    </form>
</div>
@endsection
