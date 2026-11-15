<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>فاتورة {{ $invoice->number }}</title>
    <link rel="stylesheet" href="/css/app.css">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #fff; }
            .print-sheet { box-shadow: none; border: 0; margin: 0; width: 100%; }
        }
        .print-sheet { width: 720px; margin: 20px auto; background: #fff; padding: 32px; border: 1px solid var(--border); border-radius: 8px; }
        .print-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid var(--chrome); padding-bottom: 14px; margin-bottom: 18px; }
        .print-brand { font-size: 22px; font-weight: 700; color: var(--chrome); }
        .print-brand small { display: block; font-size: 11px; color: var(--text-3); font-weight: 400; margin-top: 3px; }
        .print-meta { text-align: left; font-size: 13px; color: var(--text-2); line-height: 1.9; }
        .print-meta strong { color: var(--text-1); }
        .party-box { display: flex; gap: 24px; margin-bottom: 18px; font-size: 13.5px; }
        .party-box > div { flex: 1; background: var(--surface); padding: 10px 14px; border-radius: 6px; line-height: 1.9; }
        .totals { margin-top: 14px; text-align: left; font-size: 14px; line-height: 2.1; }
        .totals .grand { font-size: 19px; font-weight: 700; border-top: 2px solid var(--chrome); padding-top: 6px; }
        .print-foot { margin-top: 26px; display: flex; justify-content: space-between; color: var(--text-3); font-size: 11.5px; }
    </style>
</head>
<body>
<div class="print-sheet">
    <div class="print-header">
        <div>
            <div class="print-brand">{{ auth()->user()->company?->name ?? 'StockFlow' }}<small>فاتورة مبيعات</small></div>
        </div>
        <div class="print-meta">
            <div>رقم الفاتورة: <strong>{{ $invoice->number }}</strong></div>
            <div>التاريخ: <strong>{{ $invoice->issued_at->format('Y/m/d H:i') }}</strong></div>
            <div>النوع: <strong>{{ $invoice->type === 'cash' ? 'نقدي' : 'آجل' }}</strong></div>
        </div>
    </div>

    <div class="party-box">
        <div>
            <strong>العميل:</strong> {{ $invoice->customer->name }}<br>
            @if($invoice->customer->phone)<strong>الجوال:</strong> <span dir="ltr">{{ $invoice->customer->phone }}</span><br>@endif
            @if($invoice->customer->address)<strong>العنوان:</strong> {{ $invoice->customer->address }}@endif
        </div>
        <div>
            <strong>المخزن:</strong> {{ $invoice->warehouse->name }}<br>
            @if($invoice->rep)<strong>المندوب:</strong> {{ $invoice->rep->name }}<br>@endif
            <strong>مصدر:</strong> {{ $invoice->channel === 'mobile' ? 'تطبيق المندوب' : 'الإدارة' }}
        </div>
    </div>

    <table class="table">
        <thead>
        <tr><th>#</th><th>الصنف</th><th>الكمية</th><th>السعر</th><th>الإجمالي</th></tr>
        </thead>
        <tbody>
        @foreach($invoice->items as $i => $item)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $item->product->name }}@if($item->product->unit) ({{ $item->product->unit->short_name }})@endif</td>
                <td class="num">{{ $item->qty + 0 }}</td>
                <td class="num">{{ number_format($item->unit_price, 2) }}</td>
                <td class="num">{{ number_format($item->total, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div>الإجمالي الفرعي: <strong>{{ number_format($invoice->subtotal, 2) }}</strong></div>
        @if($invoice->discount > 0)<div>الخصم: <strong>{{ number_format($invoice->discount, 2) }}</strong></div>@endif
        @if($invoice->type === 'credit')<div>المحصّل نقدًا: <strong>{{ number_format($invoice->collected_amount, 2) }}</strong></div>@endif
        <div class="grand">الإجمالي المستحق: {{ number_format($invoice->total, 2) }}</div>
    </div>

    @if($invoice->note)<p style="font-size:12.5px; color:var(--text-2); margin-top:14px">ملاحظات: {{ $invoice->note }}</p>@endif

    <div class="print-foot">
        <span>أُنشئت بواسطة StockFlow</span>
        <span>توقيع العميل: ......................</span>
    </div>
</div>

<div class="no-print" style="text-align:center; padding: 10px 0 30px;">
    <button class="btn btn-primary" onclick="window.print()">🖨 طباعة</button>
    <a class="btn btn-secondary" href="{{ route('invoices.show', $invoice) }}">رجوع</a>
</div>
</body>
</html>
