@extends('layouts.app')
@section('title', 'مرتجعات المشتريات')

@section('content')
<div class="page-header">
    <button type="button" class="btn btn-secondary btn-sm export-pdf" onclick="window.print()">تصدير PDF</button>
    <div><h1 class="page-title">مرتجعات المشتريات</h1><p class="page-sub">{{ $returns->total() }} مرتجع</p></div>
    @can('purchases.create')
        <div class="page-actions">
            <a class="btn btn-primary" href="{{ route('purchase-returns.create') }}">مرتجع جديد</a>
        </div>
    @endcan
</div>

<div class="card no-print">
    <form method="GET" class="filters" style="display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end; padding:14px">
        <div>
            <label class="label">المورد</label>
            <select class="input" name="supplier_id">
                <option value="">الكل</option>
                @foreach($suppliers as $s)
                    <option value="{{ $s->id }}" {{ ($filters['supplier_id'] ?? '') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="label">النوع</label>
            <select class="input" name="type">
                <option value="">الكل</option>
                <option value="supplier" {{ ($filters['type'] ?? '') === 'supplier' ? 'selected' : '' }}>مرتجع للمورد</option>
                <option value="damage" {{ ($filters['type'] ?? '') === 'damage' ? 'selected' : '' }}>إهلاك تالف</option>
            </select>
        </div>
        <div>
            <label class="label">من</label>
            <input class="input" type="date" name="from" value="{{ $filters['from'] ?? '' }}">
        </div>
        <div>
            <label class="label">إلى</label>
            <input class="input" type="date" name="to" value="{{ $filters['to'] ?? '' }}">
        </div>
        <button class="btn btn-secondary" type="submit">تصفية</button>
    </form>
</div>

<div class="card">
    @if($returns->isEmpty())
        <div class="empty">لا توجد مرتجعات مشتريات</div>
    @else
        <table class="table">
            <thead><tr><th>الرقم</th><th>المورد</th><th>النوع</th><th>المخزن</th><th>الإجمالي</th><th>التاريخ</th><th></th></tr></thead>
            <tbody>
            @foreach($returns as $return)
                <tr>
                    <td>{{ $return->number }}</td>
                    <td>{{ $return->supplier?->name }}</td>
                    <td>@if($return->type === 'supplier')<span class="badge badge-info">مرتجع للمورد</span>@else<span class="badge badge-error">إهلاك تالف</span>@endif</td>
                    <td>{{ $return->warehouse?->name }}</td>
                    <td class="num">{{ number_format($return->total, 2) }}</td>
                    <td class="num">{{ $return->issued_at->format('Y-m-d') }}</td>
                    <td><a class="btn btn-secondary btn-sm" href="{{ route('purchase-returns.show', $return) }}">عرض</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>

{{ $returns->links() }}
@endsection
