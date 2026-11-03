<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks development-only pages (API documentation, default welcome page) from
 * being reached in the Production environment. All other environments — local,
 * development, staging, testing — are unaffected.
 */
class BlockInProduction
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('production')) {
            abort(403);
        }

        return $next($request);
    }
}
