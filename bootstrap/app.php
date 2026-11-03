<?php

use App\Services\Security\Middleware\NonceMiddleware;
use App\Services\Security\Middleware\ThreatDetectionMiddleware;
use App\Services\Security\Services\SecurityLogService;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function () {
            Route::prefix('api')
                ->middleware('api')
                ->group(base_path('routes/security-api.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {

        $middleware->append(\App\Http\Middleware\SetLocaleFromHeader::class);

        $middleware->redirectGuestsTo(fn () => route('api.login'));

        $middleware->alias([
            'abilities' => CheckAbilities::class,
            'ability' => CheckForAnyAbility::class,
            'security.threat' => ThreatDetectionMiddleware::class,
            'security.nonce' => NonceMiddleware::class,
        ]);

        /*
        |--------------------------------------------------------------
        | Security Middleware Group — Full Protection
        |--------------------------------------------------------------
        | Apply to ALL protected API routes (CRUD, data access, etc.)
        | Pipeline order: cheap checks first, expensive checks last.
        */
        $middleware->appendToGroup('security', [
            ThreatDetectionMiddleware::class,
            \App\Services\Security\Middleware\IpFilterMiddleware::class,
            \App\Services\Security\Middleware\RateLimitMiddleware::class,
            \App\Services\Security\Middleware\ForceJsonMiddleware::class,
            \App\Services\Security\Middleware\PayloadSizeLimitMiddleware::class,
            \App\Services\Security\Middleware\SecurityHeadersMiddleware::class,
            \App\Services\Security\Middleware\CorsMiddleware::class,
            \App\Services\Security\Middleware\ApiKeyMiddleware::class,
            \App\Services\Security\Middleware\TimestampAntiReplayMiddleware::class,
            \App\Services\Security\Middleware\SignatureMiddleware::class,
            NonceMiddleware::class,
        ]);

        /*
        |--------------------------------------------------------------
        | Security Public Group — Lighter Protection
        |--------------------------------------------------------------
        | For auth routes (login, forgot-password) that don't need
        | API key, timestamp, or signature validation yet.
        */
        $middleware->appendToGroup('security-public', [
            ThreatDetectionMiddleware::class,
            \App\Services\Security\Middleware\IpFilterMiddleware::class,
            \App\Services\Security\Middleware\RateLimitMiddleware::class,
            \App\Services\Security\Middleware\ForceJsonMiddleware::class,
            \App\Services\Security\Middleware\PayloadSizeLimitMiddleware::class,
            \App\Services\Security\Middleware\SecurityHeadersMiddleware::class,
            \App\Services\Security\Middleware\CorsMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $helper = new class
        {
            use App\Http\Traits\HttpResponsesTrait;
        };
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            return response()->json(['status' => false, 'message' => 'Unauthenticated'], 401);
        });
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) use ($helper) {
            $errors = $e->errors();
            $firstMessage = reset($errors);
            $firstMessageText = is_array($firstMessage) ? ($firstMessage[0] ?? '') : $firstMessage;

            return $helper->errors(
                message: $firstMessageText ?: $e->getMessage(),
                data: $errors,
                status: 422,
            );
        });
        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, $request) use ($helper) {
            if ($request->expectsJson()) {
                return $helper->failure(__('messages.resource_not_found'), 404);
            }
        });
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, $request) use ($helper) {
            $status = $e->getStatusCode();
            return match ($status) {
                401 => $helper->failure(__('messages.unauthorized'), 401),
                403 => $helper->failure($e->getMessage() ?: __('messages.you_are_not_allowed_to_perform_this_action'), 403),
                404 => (function () use ($helper, $request) {
                    // ── 404 Probing Detection ──
                    // Track repeated 404 hits per IP. If an IP triggers too many
                    // 404s in a short window, auto-block it (likely a scanner/bot).
                    if (config('security.probing_detection.enabled', false)) {
                        SecurityLogService::track404($request->ip());
                    }
                    // ── Enumeration Masking ──
                    // Always return the same generic 404 message regardless of
                    // whether the resource exists or not. Prevents attackers from
                    // guessing valid IDs by comparing error messages.
                    return $helper->failure(__('messages.resource_not_found'), 404);
                })(),
                405 => $helper->failure(__('messages.method_not_allowed'), 405),
                419 => $helper->failure(__('messages.page_expired'), 419),
                default => $helper->failure(__('messages.server_error'), 500),
            };
        });

    })->create();
