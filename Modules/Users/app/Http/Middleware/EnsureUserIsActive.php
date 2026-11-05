<?php

namespace Modules\Users\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Users\Models\User;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('sanctum');

        if ($user instanceof User && ! $user->is_active) {
            return response()->json([
                'success' => false,
                'message' => __('users::messages.account_suspended'),
            ], 403);
        }

        return $next($request);
    }
}
