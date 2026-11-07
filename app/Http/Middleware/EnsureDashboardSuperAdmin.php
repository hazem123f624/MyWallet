<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureDashboardSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $admin = Auth::guard('admins')->user();

        if (! $admin || ! $admin->isSuperAdmin()) {
            abort(403);
        }

        return $next($request);
    }
}
