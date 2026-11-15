@extends('layouts.app')
@section('title', 'تحويل '.$transfer->number)

@section('content')
<div class="page-header">
    <h1 class="page-title">تحويل {{ $transfer->number }}</h1>
    <div class="page-actions"><a class="btn btn-secondary" href="{{ route('transfers.index') }}">رجوع</a></div>
</div>

<div class="card" style="margin-bottom:14px">
    <div class="card-pad" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap:12px">
        <div><div class="stat-label">من</div><strong>{{ $transfer->fromWarehouse->name }}</strong></div>
        <div><div class="stat-label">إلى</div><strong>{{ $transfer->toWarehouse->name }}</strong></div>
        <div><div class="stat-label">التاريخ</div><strong class="num">{{ $transfer->created_at->format('Y-m-d H:i') }}</strong></div>
    </div>
</div>

<div class="card">
    <table class="table">
        <thead><tr><th>الصنف</th><th>الكمية</th></tr></thead>
        <tbody>
        @foreach($transfer->items as $item)
            <tr><td>{{ $item->product->name }}</td><td class="num">{{ $item->qty + 0 }}</td></tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
