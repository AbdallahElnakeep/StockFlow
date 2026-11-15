<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'phone' => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:100'],
        ]);

        $phone = trim(str_replace([' ', '-', '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'], ['', '', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'], $credentials['phone']));

        $user = User::where('phone', $phone)->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages(['phone' => ['بيانات الدخول غير صحيحة']]);
        }

        if (!$user->is_active || ($user->company && !$user->company->is_active)) {
            return response()->json(['message' => 'هذا الحساب أو الشركة موقوف'], 403);
        }

        $user->update(['last_login_at' => now()]);
        $user->load('custodyWarehouse');

        return response()->json([
            'token' => $user->createToken($credentials['device_name'])->plainTextToken,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'is_rep' => $user->custodyWarehouse !== null,
                'custody_warehouse_id' => $user->custodyWarehouse?->id,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'تم تسجيل الخروج']);
    }
}
