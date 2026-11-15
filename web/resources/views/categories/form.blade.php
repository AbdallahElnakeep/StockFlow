@extends('layouts.app')
@section('title', $category->exists ? 'تعديل تصنيف' : 'تصنيف جديد')

@section('content')
<div class="page-header"><h1 class="page-title">{{ $category->exists ? 'تعديل تصنيف' : 'تصنيف جديد' }}</h1></div>

@if ($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif

<div class="card card-pad" style="max-width:440px">
    <form method="POST" action="{{ $category->exists ? route('categories.update', $category) : route('categories.store') }}">
        @csrf
        @if($category->exists) @method('PUT') @endif
        <div class="field">
            <label for="name">اسم التصنيف *</label>
            <input class="input" id="name" name="name" value="{{ old('name', $category->name) }}" required>
        </div>
        <div class="field">
            <label for="parent_id">تصنيف أب</label>
            <select class="input" id="parent_id" name="parent_id">
                <option value="">— بدون —</option>
                @foreach($parents as $parent)
                    <option value="{{ $parent->id }}" {{ old('parent_id', $category->parent_id) == $parent->id ? 'selected' : '' }}>{{ $parent->name }}</option>
                @endforeach
            </select>
        </div>
        <div style="display:flex; gap:8px">
            <button class="btn btn-primary" type="submit">حفظ</button>
            <a class="btn btn-secondary" href="{{ route('categories.index') }}">إلغاء</a>
        </div>
    </form>
</div>
@endsection
