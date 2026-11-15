@extends('layouts.app')
@section('title', 'استيراد العملاء')

@section('content')
<div class="page-header"><h1 class="page-title">استيراد عملاء من ملف CSV</h1></div>

<div class="card card-pad" style="max-width:640px">
    <p style="font-size:13px; color:var(--text-2); line-height:2">
        ارفع ملف CSV بأعمدة: <strong>الاسم (إلزامي)، الجوال، المنطقة، العنوان، حد الآجل</strong>.<br>
        الصفوف بموبايل مكرر أو بدون اسم تُتجاهل تلقائيًا.
    </p>
    <div class="card" style="padding:10px 14px; font-size:12.5px; direction:ltr; text-align:left; margin-bottom:16px; background:var(--surface)">
        name,phone,area,address,credit_limit<br>
        بقالة النور,01012345678,مدينة نصر,شارع السوق,500
    </div>

    @if ($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif

    <form method="POST" action="{{ route('customers.import') }}" enctype="multipart/form-data">
        @csrf
        <div class="field">
            <label for="file">ملف CSV *</label>
            <input class="input" id="file" name="file" type="file" accept=".csv,text/csv,text/plain" required>
        </div>
        <div style="display:flex; gap:8px">
            <button class="btn btn-primary" type="submit">استيراد</button>
            <a class="btn btn-secondary" href="{{ route('customers.index') }}">إلغاء</a>
        </div>
    </form>
</div>
@endsection
