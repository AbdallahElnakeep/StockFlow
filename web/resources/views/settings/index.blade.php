@extends('layouts.app')
@section('title', 'الإعدادات')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">إعدادات الشركة</h1>
        <p class="page-sub">بيانات الشركة وقواعد العمل الأساسية</p>
    </div>
</div>

@if ($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif

<div class="card card-pad" style="max-width:640px">
    <form method="POST" action="{{ route('settings.update') }}">
        @csrf @method('PUT')

        <div class="field">
            <label for="company_name">اسم الشركة (يظهر في الفواتير) *</label>
            <input class="input" id="company_name" name="company_name" value="{{ old('company_name', $company->name) }}" required>
        </div>

        <div class="field">
            <label for="company_phone">هاتف الشركة</label>
            <input class="input num" id="company_phone" name="company_phone" value="{{ old('company_phone', $company->phone) }}">
        </div>

        <div style="height:14px"></div>

        <label style="display:flex; align-items:flex-start; gap:10px; padding:10px 0; border-top:1px solid var(--border-hair)">
            <input type="hidden" name="enforce_credit_limit" value="0">
            <input type="checkbox" name="enforce_credit_limit" value="1" style="width:17px; height:17px; accent-color:var(--brand); margin-top:2px" {{ old('enforce_credit_limit', $settings['enforce_credit_limit'] ?? '1') ? 'checked' : '' }}>
            <span>
                <strong style="display:block; font-size:13.5px">فرض حد الآجل على العملاء</strong>
                <span style="font-size:12.5px; color:var(--text-3)">رفض البيع الآجل إذا تجاوز العميل حده المعتمد (الحد = 0 يعني نقدي فقط)</span>
            </span>
        </label>

        <label style="display:flex; align-items:flex-start; gap:10px; padding:10px 0; border-top:1px solid var(--border-hair)">
            <input type="hidden" name="allow_negative_stock" value="0">
            <input type="checkbox" name="allow_negative_stock" value="1" style="width:17px; height:17px; accent-color:var(--brand); margin-top:2px" {{ old('allow_negative_stock', $settings['allow_negative_stock'] ?? '0') ? 'checked' : '' }}>
            <span>
                <strong style="display:block; font-size:13.5px">السماح بالبيع عند نفاد الرصيد</strong>
                <span style="font-size:12.5px; color:var(--text-3)">غير موصى به — يفتح باب اختلافات المخزون (حاليًا الرصيد السالب مرفوض دائمًا)</span>
            </span>
        </label>

        <div style="padding-top:14px; border-top:1px solid var(--border-hair)">
            <button class="btn btn-primary" type="submit">حفظ الإعدادات</button>
        </div>
    </form>
</div>

<div class="card card-pad" style="max-width:640px; margin-top:14px">
    <strong style="font-size:13.5px">معلومات النظام</strong>
    <div style="font-size:12.5px; color:var(--text-2); line-height:2.2; margin-top:6px">
        <div>الإصدار: 1.0.0 (MVP)</div>
        <div>دليل الحسابات: 11 حسابًا نظاميًا (1000–5200 + الأرصدة الافتتاحية)</div>
        <div>العملة: جنيه مصري — نقاط عشرية: 2</div>
        <div>المنتج: StockFlow — نظام إدارة المخازن والتوزيع</div>
    </div>
</div>
@endsection
