@extends('layouts.app')
@section('title', $role->exists ? 'تعديل دور' : 'دور جديد')

@section('content')
<div class="page-header"><h1 class="page-title">{{ $role->exists ? 'صلاحيات دور: '.$role->name : 'دور جديد' }}</h1></div>

@if ($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif

<form method="POST" action="{{ $role->exists ? route('roles.update', $role) : route('roles.store') }}">
    @csrf
    @if($role->exists) @method('PUT') @endif

    <div class="card card-pad" style="max-width:520px; margin-bottom:14px">
        <div class="field">
            <label for="name">اسم الدور *</label>
            <input class="input" id="name" name="name" value="{{ old('name', $role->name) }}" required>
        </div>
    </div>

    <div class="card card-pad">
        <div style="font-weight:600; margin-bottom:12px">الصلاحيات — حدد "عرض" على الأقل للوحدات المسموحة</div>
        @php
            $grouped = $permissions->groupBy('module');
            $names = ['users' => 'المستخدمون', 'roles' => 'الأدوار', 'warehouses' => 'المخازن', 'products' => 'الأصناف', 'purchases' => 'المشتريات', 'inventory' => 'المخزون', 'customers' => 'العملاء', 'sales' => 'المبيعات', 'returns' => 'المرتجعات', 'payments' => 'التحصيلات', 'reps' => 'المندوبون', 'expenses' => 'المصروفات', 'reports' => 'التقارير', 'settings' => 'الإعدادات'];
        @endphp
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap:16px">
            @foreach($grouped as $module => $modulePermissions)
                <div>
                    <strong style="display:block; margin-bottom:6px">{{ $names[$module] ?? $module }}</strong>
                    @foreach($modulePermissions as $permission)
                        <label style="display:flex; align-items:center; gap:8px; font-weight:400; padding:3px 0">
                            <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" {{ in_array($permission->id, $selected) ? 'checked' : '' }}>
                            {{ $permission->name_ar }}
                        </label>
                    @endforeach
                </div>
            @endforeach
        </div>
        <div style="display:flex; gap:8px; margin-top:16px">
            <button class="btn btn-primary" type="submit">حفظ</button>
            <a class="btn btn-secondary" href="{{ route('roles.index') }}">إلغاء</a>
        </div>
    </div>
</form>
@endsection
