<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleFromHeader
{
    public function handle(Request $request, Closure $next)
    {
        $supportedLocales = ['en', 'ar'];
        $locale = $request->getPreferredLanguage($supportedLocales);
        if ($locale) {
            App::setLocale($locale);
        }
        return $next($request);
    }
}
