<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureDashboardAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $admin = Auth::guard('admins')->user();

        if (! $admin || ! $admin->is_active) {
            Auth::guard('admins')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('dashboard.login');
        }

        $admin->loadMissing(['roles.role']);

        return $next($request);
    }
}
