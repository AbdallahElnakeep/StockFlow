@extends('layouts.app')
@section('title', $warehouse->exists ? 'تعديل مخزن' : 'مخزن جديد')

@section('content')
<div class="page-header"><h1 class="page-title">{{ $warehouse->exists ? 'تعديل: '.$warehouse->name : 'إضافة مخزن رئيسي أو سيارة' }}</h1></div>

@if ($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif

<div class="card card-pad" style="max-width:520px">
    @if($warehouse->exists && $warehouse->type === 'rep')
        <div class="alert alert-info">
            هذه عهدة مندوب تُدار تلقائيًا — تُنشأ عند إسناد دور «مندوب توزيع» للمستخدم من شاشة المستخدمين، ولا تُعدَّل من هنا.
        </div>
    @endif

    <form method="POST" action="{{ $warehouse->exists ? route('warehouses.update', $warehouse) : route('warehouses.store') }}">
        @csrf
        @if($warehouse->exists) @method('PUT') @endif

        <div class="field">
            <label for="name">اسم المخزن *</label>
            <input class="input" id="name" name="name" value="{{ old('name', $warehouse->name) }}" required @if($warehouse->exists && $warehouse->type === 'rep') disabled @endif>
        </div>

        @if(!$warehouse->exists || $warehouse->type !== 'rep')
            <div class="field">
                <label for="type">النوع *</label>
                <select class="input" id="type" name="type">
                    <option value="main" {{ old('type', $warehouse->type ?? 'main') === 'main' ? 'selected' : '' }}>مخزن رئيسي (استلام وتخزين)</option>
                    <option value="van" {{ old('type', $warehouse->type) === 'van' ? 'selected' : '' }}>سيارة توزيع</option>
                </select>
                <p style="color:var(--text-3); font-size:12px; margin-top:4px">
                    عُهد المندوبين تُنشأ وتُدار تلقائيًا عند تعيين دور المندوب — لا تحتاج لإضافتها هنا
                </p>
            </div>
        @endif

        @if(!$warehouse->exists || $warehouse->type !== 'rep')
            <label style="display:flex; align-items:center; gap:8px; font-weight:400; margin-bottom:16px">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $warehouse->is_active ?? true) ? 'checked' : '' }}>
                نشط
            </label>
        @endif

        <div style="display:flex; gap:8px">
            @if(!$warehouse->exists || $warehouse->type !== 'rep')
                <button class="btn btn-primary" type="submit">حفظ</button>
            @endif
            <a class="btn btn-secondary" href="{{ route('warehouses.index') }}">رجوع</a>
        </div>
    </form>
</div>
@endsection
