@extends('layouts.app')
@section('title', 'المستخدمون')

@section('content')
<div class="page-header">
    <div><h1 class="page-title">المستخدمون</h1><p class="page-sub">{{ $users->total() }} مستخدم</p></div>
    @can('users.create')<div class="page-actions"><a class="btn btn-primary" href="{{ route('users.create') }}">مستخدم جديد</a></div>@endcan
</div>

<div class="card">
    @if($users->isEmpty())
        <div class="empty">لا يوجد مستخدمون</div>
    @else
        <table class="table">
            <thead><tr><th>الاسم</th><th>الجوال</th><th>الأدوار</th><th>العهدة</th><th>آخر دخول</th><th>الحالة</th><th></th></tr></thead>
            <tbody>
            @foreach($users as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td class="num">{{ $user->phone }}</td>
                    <td>{{ $user->roles->pluck('name')->implode('، ') ?: '—' }}</td>
                    <td>
                        @if($user->custodyWarehouse)
                            <a href="{{ route('reps.show', $user) }}">متابعة العهدة ←</a>
                        @else
                            <span style="color:var(--text-3)">—</span>
                        @endif
                    </td>
                    <td class="num">{{ $user->last_login_at?->format('Y-m-d H:i') ?? 'لم يدخل' }}</td>
                    <td>@if($user->is_active)<span class="badge badge-success">نشط</span>@else<span class="badge badge-error">موقوف</span>@endif</td>
                    <td>
                        @can('users.update')<a class="btn btn-secondary btn-sm" href="{{ route('users.edit', $user) }}">تعديل</a>@endcan
                        @can('users.delete')
                            @if($user->is_active && $user->id !== auth()->id())
                                <form method="POST" action="{{ route('users.destroy', $user) }}" style="display:inline" onsubmit="return confirm('إيقاف هذا المستخدم؟')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-danger btn-sm" type="submit">إيقاف</button>
                                </form>
                            @endif
                        @endcan
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>

{{ $users->links() }}
@endsection
