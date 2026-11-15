@extends('layouts.app')
@section('title', 'مصروف جديد')

@section('content')
<div class="page-header"><h1 class="page-title">تسجيل مصروف</h1></div>

@if ($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif

<div class="card card-pad" style="max-width:520px">
    <form method="POST" action="{{ route('expenses.store') }}">
        @csrf
        <div class="form-row">
            <div class="field">
                <label for="category">التصنيف *</label>
                <select class="input" id="category" name="category" required>
                    @foreach($categories as $category)<option value="{{ $category }}">{{ $category }}</option>@endforeach
                </select>
            </div>
            <div class="field">
                <label for="amount">القيمة *</label>
                <input class="input num" id="amount" name="amount" type="number" step="0.01" min="0.01" required>
            </div>
        </div>
        <div class="field">
            <label for="rep_user_id">المندوب (اختياري)</label>
            <select class="input" id="rep_user_id" name="rep_user_id">
                <option value="">— مصروف إداري —</option>
                @foreach($reps as $rep)<option value="{{ $rep->id }}">{{ $rep->name }}</option>@endforeach
            </select>
        </div>
        <div class="field"><label for="note">ملاحظة</label><input class="input" id="note" name="note"></div>
        <div style="display:flex; gap:8px">
            <button class="btn btn-primary" type="submit">حفظ</button>
            <a class="btn btn-secondary" href="{{ route('expenses.index') }}">إلغاء</a>
        </div>
    </form>
</div>
@endsection
