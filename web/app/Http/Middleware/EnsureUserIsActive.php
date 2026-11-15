<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->is_active === false) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'تم إيقاف هذا الحساب'], 403);
            }

            auth('web')->logout();

            return redirect()->route('login')->withErrors(['phone' => 'تم إيقاف هذا الحساب']);
        }

        return $next($request);
    }
}
