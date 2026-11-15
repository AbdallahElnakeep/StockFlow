@extends('layouts.app')
@section('title', $supplier->exists ? 'تعديل مورد' : 'مورد جديد')

@section('content')
<div class="page-header"><h1 class="page-title">{{ $supplier->exists ? 'تعديل: '.$supplier->name : 'مورد جديد' }}</h1></div>

@if ($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif

<div class="card card-pad" style="max-width:520px">
    <form method="POST" action="{{ $supplier->exists ? route('suppliers.update', $supplier) : route('suppliers.store') }}">
        @csrf
        @if($supplier->exists) @method('PUT') @endif
        <div class="field">
            <label for="name">اسم المورد *</label>
            <input class="input" id="name" name="name" value="{{ old('name', $supplier->name) }}" required>
        </div>
        <div class="field">
            <label for="phone">الجوال</label>
            <input class="input" id="phone" name="phone" value="{{ old('phone', $supplier->phone) }}">
        </div>
        <div class="field">
            <label for="address">العنوان</label>
            <textarea class="input" id="address" name="address" rows="2">{{ old('address', $supplier->address) }}</textarea>
        </div>
        <div class="field"><label for="note">ملاحظات</label><input class="input" id="note" name="note" value="{{ old('note', $supplier->note) }}"></div>
        <div style="display:flex; gap:8px">
            <button class="btn btn-primary" type="submit">حفظ</button>
            <a class="btn btn-secondary" href="{{ route('suppliers.index') }}">إلغاء</a>
        </div>
    </form>
</div>
@endsection
