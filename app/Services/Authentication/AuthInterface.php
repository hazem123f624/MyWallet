<?php

namespace App\Services\Authentication;

/**
 * Authentication Interface
 *
 * Defines the contract that all authentication drivers (Sanctum, JWT, Passport) must implement.
 * Each driver provides its own implementation for login, logout, token refresh,
 * forgot password, and reset password flows.
 *
 * This interface is used by AuthFactory to return the correct driver instance.
 *
 * @see \App\Services\Authentication\AuthFactory
 * @see \App\Services\Authentication\types\SanctumRepository
 * @see \App\Services\Authentication\types\JWTRepository
 * @see \App\Services\Authentication\types\PassportRepository
 */
interface AuthInterface
{
    /**
     * Authenticate a user and optionally create an access token.
     *
     * Usage:
     *   $auth = AuthFactory::make('sanctum', 'admins');
     *   $result = $auth->login($request->only('email', 'password'));
     *   // $result = ['user' => AdminsDTO, 'token' => '...', 'token_type' => 'Bearer', ...]
     *
     *   // Login without token (e.g. for session-based auth):
     *   $result = $auth->login($request->only('email', 'password'), false);
     *   // $result = ['user' => AdminsDTO]
     *
     * @param  array  $request     Credentials array, typically ['email' => '...', 'password' => '...']
     * @param  bool   $createToken Whether to generate an access token (default: true)
     * @return array  Returns ['user' => DTO] or ['user' => DTO, 'token' => '...', 'token_type' => '...', ...]
     *
     * @throws \Illuminate\Validation\ValidationException If credentials are invalid
     * @throws \Exception If guard is not defined in config
     */
    public function login($request, $createToken = true);

    /**
     * Logout the currently authenticated user.
     *
     * Revokes/deletes the current access token depending on the driver:
     * - Sanctum: deletes the current personal access token
     * - JWT: invalidates the JWT token
     * - Passport: revokes the OAuth token
     *
     * Usage:
     *   $auth = AuthFactory::make('sanctum', 'admins');
     *   $auth->logout();
     *
     * @return void
     */
    public function logout($ids = []);

    /**
     * Refresh the current access token.
     *
     * Creates a new token and invalidates the old one.
     * The user must be authenticated when calling this method.
     *
     * Usage:
     *   $auth = AuthFactory::make('jwt', 'admins');
     *   $result = $auth->refreshToken();
     *   // $result = ['user' => AdminsDTO, 'token' => 'new-token', 'token_type' => 'bearer', ...]
     *
     * @return array Returns ['user' => DTO, 'token' => '...', 'token_type' => '...', ...]
     *
     * @throws \Illuminate\Validation\ValidationException If user is unauthenticated or token cannot be refreshed
     */
    public function refreshToken(): array;

    /**
     * Initiate the forgot password flow.
     *
     * Generates an OTP and sends it via email or SMS, then creates a
     * temporary change_password_token stored on the user model.
     *
     * Usage:
     *   $auth = AuthFactory::make('sanctum', 'admins');
     *
     *   // Via email:
     *   $token = $auth->forgotPassword(['email' => 'user@example.com'], 'email');
     *
     *   // Via SMS:
     *   $token = $auth->forgotPassword(['phone' => '01xxxxxxxxx'], 'phone');
     *
     * @param  array   $request  Must contain 'email' or 'phone' key depending on $type
     * @param  string  $type     The channel to send OTP through: 'email' or 'phone'
     * @return string  The change_password_token (valid for 10 minutes) needed for resetPassword()
     *
     * @throws \Illuminate\Validation\ValidationException If user is not found
     */
    public function forgotPassword($request, $type): string;

    /**
     * Reset the user's password.
     *
     * Validates the change_password_token and OTP, then updates the password.
     * Must be called after forgotPassword() with the token returned from it.
     *
     * Usage:
     *   $auth = AuthFactory::make('sanctum', 'admins');
     *   $auth->resetPassword([
     *       'email'    => 'user@example.com',
     *       'token'    => $changePasswordToken,  // from forgotPassword()
     *       'otp'      => '123456',               // OTP sent to user
     *       'password' => 'newSecurePassword',
     *   ], 'email');
     *
     * @param  array   $request  Must contain: the identifier (email/phone), 'token', 'otp', and 'password'
     * @param  string  $type     The identifier type used: 'email' or 'phone'
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException If user not found, token expired, or OTP invalid
     */
    public function resetPassword($request, $type);
}
