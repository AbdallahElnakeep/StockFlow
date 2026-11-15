<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Services\RepCustodyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function __construct(private RepCustodyService $custodyService) {}

    public function index()
    {
        $users = User::with('roles', 'custodyWarehouse')->orderBy('name')->paginate(20);

        return view('users.index', ['users' => $users]);
    }

    public function create()
    {
        return view('users.form', [
            'user' => new User(),
            'roles' => Role::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $user = User::create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
            'is_active' => $data['is_active'] ?? true,
        ]);
        $user->roles()->sync($data['roles'] ?? []);
        $this->custodyService->syncForRoles($user, $data['roles'] ?? []);

        return redirect()->route('users.index')->with('success', 'تم إضافة المستخدم');
    }

    public function edit(User $user)
    {
        return view('users.form', [
            'user' => $user,
            'roles' => Role::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $this->validated($request, $user->id);
        $willBeRep = $this->custodyService->rolesIncludeRep($data['roles'] ?? []);

        if (!$willBeRep && $this->custodyService->hasActiveCustody($user)) {
            if (!$this->custodyService->releaseCustody($user)) {
                return back()->withInput()->with('error', 'لا يمكن سحب دور المندوب — عهدته بها بضاعة أو مستندات غير مُسوّاة. سوِّ العهدة أولًا من صفحة المندوبين ثم أعد المحاولة');
            }
        }

        $user->update(array_filter([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'password' => !empty($data['password']) ? Hash::make($data['password']) : null,
            'is_active' => $data['is_active'] ?? true,
        ], fn ($v) => $v !== null));
        $user->roles()->sync($data['roles'] ?? []);

        if ($willBeRep) {
            $this->custodyService->ensureCustody($user->refresh());
        }

        return redirect()->route('users.index')->with('success', 'تم تحديث المستخدم');
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'لا يمكنك حذف حسابك');
        }

        if ($user->custodyWarehouse()->whereHas('balances', fn ($q) => $q->where('qty', '!=', 0))->exists()) {
            return back()->with('error', 'لا يمكن إيقاف المندوب وبضاعة في عهدته — سوِّ العهدة أولًا');
        }

        $this->custodyService->syncForRoles($user, []);
        $user->update(['is_active' => false]);

        return redirect()->route('users.index')->with('success', 'تم إيقاف المستخدم');
    }

    private function validated(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone'.($id ? ",{$id}" : '')],
            'password' => [$id ? 'nullable' : 'required', 'string', 'min:6'],
            'is_active' => ['nullable', 'boolean'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['exists:roles,id'],
        ]);
    }
}
