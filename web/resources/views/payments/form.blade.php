@extends('layouts.app')
@section('title', $direction === 'in' ? 'تحصيل من عميل' : 'سداد لمورد')

@section('content')
<div class="page-header"><h1 class="page-title">{{ $direction === 'in' ? 'تحصيل من عميل' : 'سداد لمورد' }}</h1></div>

@if ($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif

<div class="card card-pad" style="max-width:520px">
    <form method="POST" action="{{ route('payments.store') }}">
        @csrf
        <input type="hidden" name="direction" value="{{ $direction }}">

        <div class="field">
            <label for="payable_id">{{ $direction === 'in' ? 'العميل *' : 'المورد *' }}</label>
            <select class="input" id="payable_id" name="payable_id" required>
                <option value="">— اختر —</option>
                @if($direction === 'in')
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }} — مدين بـ {{ number_format($customer->balance, 2) }}</option>
                    @endforeach
                @else
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                    @endforeach
                @endif
            </select>
        </div>

        <div class="form-row">
            <div class="field">
                <label for="amount">القيمة *</label>
                <input class="input num" id="amount" name="amount" type="number" step="0.01" min="0.01" required>
            </div>
            <div class="field">
                <label for="method">الطريقة *</label>
                <select class="input" id="method" name="method" required onchange="document.getElementById('cheque-fields').style.display = this.value === 'cheque' ? 'grid' : 'none'">
                    <option value="cash">نقدي</option>
                    <option value="cheque">شيك</option>
                    <option value="transfer">تحويل بنكي</option>
                </select>
            </div>
            <div class="form-row" id="cheque-fields" style="display:none">
                <div class="field">
                    <label for="cheque_no">رقم الشيك</label>
                    <input class="input num" id="cheque_no" name="cheque_no" placeholder="اختياري">
                </div>
                <div class="field">
                    <label for="due_date">تاريخ الاستحقاق</label>
                    <input class="input" id="due_date" name="due_date" type="date">
                </div>
            </div>
        </div>

        <div class="field"><label for="note">ملاحظات</label><input class="input" id="note" name="note"></div>

        <div style="display:flex; gap:8px">
            <button class="btn btn-primary" type="submit">حفظ</button>
            <a class="btn btn-secondary" href="{{ route('payments.index') }}">إلغاء</a>
        </div>
    </form>
</div>
@endsection
