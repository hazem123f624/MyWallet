<?php

namespace App\Services\Authentication;

use App\Services\Authentication\types\JWTRepository;
use App\Services\Authentication\types\PassportRepository;
use App\Services\Authentication\types\SanctumRepository;

/**
 * Authentication Factory
 *
 * Factory class that creates the appropriate authentication driver instance
 * based on the specified type (sanctum, jwt, passport) and guard name.
 *
 * This class reads guard configuration from `config/auth_guards.php` under the key
 * `types`. Each guard entry should contain:
 *   - 'guard'    => string  (the Laravel guard name, e.g. 'admins')
 *   - 'model'    => string  (the Eloquent model class, e.g. Admins::class)
 *   - 'dtoClass' => string  (the DTO class for response formatting, e.g. AdminsDTO::class)
 *
 * Config Example (config/auth_guards.php):
 *   'types' => [
 *       'admins' => [
 *           'guard'    => 'admins',
 *           'model'    => \Modules\Admins\Models\Admins::class,
 *           'dtoClass' => \Modules\Admins\DTOs\AdminsDTO::class,
 *       ],
 *   ],
 *
 * Usage Examples:
 *
 *   // In a Controller - Login with Sanctum:
 *   $auth = AuthFactory::make('sanctum', 'admins');
 *   $result = $auth->login($request->only('email', 'password'));
 *
 *   // In a Controller - Login with JWT:
 *   $auth = AuthFactory::make('jwt', 'admins');
 *   $result = $auth->login($request->only('email', 'password'));
 *
 *   // In a Controller - Login with Passport:
 *   $auth = AuthFactory::make('passport', 'admins');
 *   $result = $auth->login($request->only('email', 'password'));
 *
 *   // Full authentication flow:
 *   $auth = AuthFactory::make('sanctum', 'admins');
 *   $loginResult   = $auth->login($request->only('email', 'password'));
 *   $refreshResult = $auth->refreshToken();
 *   $auth->logout();
 *
 *   // Forgot & Reset Password flow:
 *   $auth  = AuthFactory::make('sanctum', 'admins');
 *   $token = $auth->forgotPassword(['email' => 'user@example.com'], 'email');
 *   $auth->resetPassword([
 *       'email'    => 'user@example.com',
 *       'token'    => $token,
 *       'otp'      => '123456',
 *       'password' => 'newPassword123',
 *   ], 'email');
 *
 * @see \App\Services\Authentication\AuthInterface
 */
class AuthFactory
{
    /**
     * Create an authentication driver instance.
     *
     * @param  string  $type   The authentication driver: 'sanctum', 'jwt', or 'passport'
     * @param  string  $guard  The guard name as defined in config('auth_guards.types'), e.g. 'admins'
     * @return AuthInterface   The authentication driver instance
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException (500) If guard name is not found in config
     * @throws \Exception If $type is not one of the supported drivers
     */
    public static function make(string $type, string $guard): AuthInterface
    {
        $guardData = config('auth_guards.types');
        if (! array_key_exists($guard, $guardData)) {
            abort(500, 'Invalid auth type');
        }

        return match ($type) {
            'sanctum' => new SanctumRepository($guardData[$guard]),
            'jwt' => new JWTRepository($guardData[$guard]),
            'passport' => new PassportRepository($guardData[$guard]),
            default => throw new \Exception('Invalid auth type'),
        };
    }
}
