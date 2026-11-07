<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserDashboard
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('users')->user();

        if (! $user || ! $user->is_active || ! $user->phone_verified_at) {
            Auth::guard('users')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('user.login');
        }

        return $next($request);
    }
}
