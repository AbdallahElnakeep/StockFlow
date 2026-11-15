<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::withCount('permissions')->withCount('users')->orderBy('name')->paginate(20);

        return view('roles.index', ['roles' => $roles]);
    }

    public function create()
    {
        return view('roles.form', [
            'role' => new Role(),
            'permissions' => Permission::orderBy('module')->orderBy('action')->get(),
            'selected' => [],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $role = Role::create(['name' => $data['name']]);
        $role->permissions()->sync($data['permissions'] ?? []);

        return redirect()->route('roles.index')->with('success', 'تم إضافة الدور');
    }

    public function edit(Role $role)
    {
        return view('roles.form', [
            'role' => $role,
            'permissions' => Permission::orderBy('module')->orderBy('action')->get(),
            'selected' => $role->permissions()->pluck('permission_id')->all(),
        ]);
    }

    public function update(Request $request, Role $role)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $role->update(['name' => $data['name']]);
        $role->permissions()->sync($data['permissions'] ?? []);

        return redirect()->route('roles.index')->with('success', 'تم تحديث الدور');
    }

    public function destroy(Role $role)
    {
        if ($role->is_system) {
            return back()->with('error', 'لا يمكن حذف دور نظامي');
        }

        if ($role->users()->exists()) {
            return back()->with('error', 'الدور مخصص لمستخدمين — انقلهم أولًا');
        }

        $role->delete();

        return redirect()->route('roles.index')->with('success', 'تم حذف الدور');
    }
}
