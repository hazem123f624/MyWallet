<?php

use App\Services\Security\Controllers\ApiKeyController;
use App\Services\Security\Services\NonceService;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Security API Routes
|--------------------------------------------------------------------------
| These routes handle API key management (info + renewal) and nonce
| generation. Protected by the 'security' middleware group — requires
| valid API key + timestamp + signature to access.
*/

Route::middleware(['security'])->prefix('v1/security')->group(function () {
    // Check key status & expiration
    Route::get('key-info', [ApiKeyController::class, 'keyInfo'])->name('security.key-info');
    // Renew (rotate) the key — returns new credentials
    Route::post('renew-key', [ApiKeyController::class, 'renewKey'])->name('security.renew-key');

    // Generate a one-time nonce for anti-replay protection
    Route::get('nonce', function () {
        if (! config('security.nonce.enabled')) {
            return response()->json([
                'status' => false,
                'message' => 'Nonce protection is not enabled.',
            ], 404);
        }

        $nonce = NonceService::generate();

        return response()->json([
            'status' => true,
            'data' => [
                'nonce' => $nonce,
                'ttl_seconds' => config('security.nonce.ttl_seconds', 300),
                'header_name' => config('security.nonce.header_name', 'X-NONCE'),
            ],
        ]);
    })->name('security.nonce');
});
