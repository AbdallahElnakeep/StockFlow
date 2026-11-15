@extends('layouts.app')
@section('title', $unit->exists ? 'تعديل وحدة' : 'وحدة جديدة')

@section('content')
<div class="page-header"><h1 class="page-title">{{ $unit->exists ? 'تعديل وحدة' : 'وحدة جديدة' }}</h1></div>

@if ($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif

<div class="card card-pad" style="max-width:440px">
    <form method="POST" action="{{ $unit->exists ? route('units.update', $unit) : route('units.store') }}">
        @csrf
        @if($unit->exists) @method('PUT') @endif
        <div class="field">
            <label for="name">اسم الوحدة *</label>
            <input class="input" id="name" name="name" value="{{ old('name', $unit->name) }}" placeholder="كرتونة، عبوة..." required>
        </div>
        <div class="field">
            <label for="short_name">الاختصار *</label>
            <input class="input" id="short_name" name="short_name" value="{{ old('short_name', $unit->short_name) }}" placeholder="كرت، عبوة" required>
        </div>
        <div style="display:flex; gap:8px">
            <button class="btn btn-primary" type="submit">حفظ</button>
            <a class="btn btn-secondary" href="{{ route('units.index') }}">إلغاء</a>
        </div>
    </form>
</div>
@endsection
