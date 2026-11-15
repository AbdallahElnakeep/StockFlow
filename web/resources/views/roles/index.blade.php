@extends('layouts.app')
@section('title', 'الأدوار')

@section('content')
<div class="page-header">
    <div><h1 class="page-title">الأدوار والصلاحيات</h1><p class="page-sub">{{ $roles->total() }} دور</p></div>
    @can('roles.create')<div class="page-actions"><a class="btn btn-primary" href="{{ route('roles.create') }}">دور جديد</a></div>@endcan
</div>

<div class="card">
    @if($roles->isEmpty())
        <div class="empty">لا توجد أدوار</div>
    @else
        <table class="table">
            <thead><tr><th>الدور</th><th>عدد الصلاحيات</th><th>المستخدمون</th><th>النوع</th><th></th></tr></thead>
            <tbody>
            @foreach($roles as $role)
                <tr>
                    <td>{{ $role->name }}</td>
                    <td class="num">{{ $role->permissions_count }}</td>
                    <td class="num">{{ $role->users_count }}</td>
                    <td>@if($role->is_system)<span class="badge badge-info">نظامي</span>@else<span class="badge badge-success">مخصص</span>@endif</td>
                    <td>
                        @can('roles.update')<a class="btn btn-secondary btn-sm" href="{{ route('roles.edit', $role) }}">تعديل الصلاحيات</a>@endcan
                        @if(!$role->is_system)
                            @can('roles.delete')
                                <form method="POST" action="{{ route('roles.destroy', $role) }}" style="display:inline" onsubmit="return confirm('حذف هذا الدور؟')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-danger btn-sm" type="submit">حذف</button>
                                </form>
                            @endcan
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>

{{ $roles->links() }}
@endsection
