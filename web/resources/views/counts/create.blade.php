@extends('layouts.app')
@section('title', 'جرد جديد')

@section('content')
<div class="page-header"><h1 class="page-title">بدء جرد مخزن</h1></div>

@if ($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif

<div class="card card-pad" style="max-width:520px">
    <form method="POST" action="{{ route('counts.store') }}">
        @csrf
        <div class="field">
            <label for="warehouse_id">المخزن *</label>
            <select class="input" id="warehouse_id" name="warehouse_id" required>
                <option value="">— اختر —</option>
                @foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>@endforeach
            </select>
        </div>
        <div class="field"><label for="note">ملاحظات</label><input class="input" id="note" name="note"></div>
        <p style="color:var(--text-3); font-size:12.5px">سيُجمَّد رصيد المخزن الحالي كمجموعة أصناف للجرد، ثم تسجّل الكميات الفعلية وتُنفّذ التسوية.</p>
        <div style="display:flex; gap:8px">
            <button class="btn btn-primary" type="submit">بدء الجرد</button>
            <a class="btn btn-secondary" href="{{ route('counts.index') }}">إلغاء</a>
        </div>
    </form>
</div>
@endsection
