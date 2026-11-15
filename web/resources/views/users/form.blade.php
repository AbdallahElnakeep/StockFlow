@extends('layouts.app')
@section('title', $user->exists ? 'تعديل مستخدم' : 'مستخدم جديد')

@section('content')
<div class="page-header"><h1 class="page-title">{{ $user->exists ? 'تعديل: '.$user->name : 'مستخدم جديد' }}</h1></div>

@if ($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif

<div class="card card-pad" style="max-width:560px">
    <form method="POST" action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}">
        @csrf
        @if($user->exists) @method('PUT') @endif

        <div class="form-row">
            <div class="field">
                <label for="name">الاسم *</label>
                <input class="input" id="name" name="name" value="{{ old('name', $user->name) }}" required>
            </div>
            <div class="field">
                <label for="phone">الجوال (للدخول) *</label>
                <input class="input" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" required>
            </div>
        </div>

        <div class="field" style="max-width:280px">
            <label for="password">كلمة المرور {{ $user->exists ? '(اتركها فارغة للإبقاء)' : '*' }}</label>
            <input class="input" id="password" name="password" type="password" {{ $user->exists ? '' : 'required' }}>
        </div>

        <div class="field">
            <label>الأدوار</label>
            @foreach($roles as $role)
                <label style="display:flex; align-items:center; gap:8px; font-weight:400; padding:4px 0">
                    <input type="checkbox" name="roles[]" value="{{ $role->id }}" {{ in_array($role->id, old('roles', $user->roles->pluck('id')->all())) ? 'checked' : '' }}>
                    {{ $role->name }}
                </label>
            @endforeach
            <p style="color:var(--text-3); font-size:12px; margin-top:6px">تعيين دور «مندوب توزيع» يُنشئ مخزن عهدة للمستخدم تلقائيًا</p>
        </div>

        <div style="display:flex; gap:8px">
            <button class="btn btn-primary" type="submit">حفظ</button>
            <a class="btn btn-secondary" href="{{ route('users.index') }}">إلغاء</a>
        </div>
    </form>
</div>
@endsection
