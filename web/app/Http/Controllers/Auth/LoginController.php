<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function __invoke()
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'phone' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $phone = self::normalizePhone($credentials['phone']);
        $password = trim($credentials['password']);

        $user = User::where('phone', $phone)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            Log::warning('login.failed', ['input_phone' => $credentials['phone'], 'normalized' => $phone, 'ip' => $request->ip(), 'user_exists' => (bool) $user]);

            throw ValidationException::withMessages(['phone' => __('messages.bad_creds')]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages(['phone' => __('messages.account_disabled')]);
        }

        if ($user->company && !$user->company->is_active) {
            throw ValidationException::withMessages(['phone' => 'اشتراك الشركة موقوف']);
        }

        auth()->login($user, $request->boolean('remember'));
        $user->update(['last_login_at' => now()]);

        return redirect()->intended(route('dashboard'));
    }

    public static function normalizePhone(string $phone): string
    {
        $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $latin = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $phone = str_replace($arabic, $latin, $phone);
        $phone = preg_replace('/[\s\-\(\)\.]/', '', $phone);

        if (str_starts_with($phone, '+20')) {
            $phone = substr($phone, 3);
        } elseif (str_starts_with($phone, '0020')) {
            $phone = substr($phone, 4);
        } elseif (preg_match('/^20(1[0-2])/', $phone)) {
            $phone = substr($phone, 2);
        }

        if (preg_match('/^1[0-9]{9}$/', $phone)) {
            $phone = '0'.$phone;
        }

        return $phone;
    }
}
