<?php

use App\Enums\ActiveStatus;
use App\Services\Authentication\AuthFactory;
use App\Services\Security\Models\ApiClient;
use App\Services\Security\Services\SignatureService;
use Modules\Admins\Models\Admins;
use Modules\Users\Models\User;

if (! function_exists('fakeLogin')) {
    /**
     * Authenticate an admin user with static credentials and return the token.
     */
    function fakeLogin($email = '', $password = ''): ?string
    {
        $email = $email ?: config('admins.base_admins.0.email', 'admin@base.com');
        $password = $password ?: config('admins.base_admins.0.password', 'P@ssw0rd');

        $admin = Admins::where('email', $email)->first();
        if (! $admin) {
            Admins::create([
                'name' => 'Default Admin',
                'email' => $email,
                'password' => Hash::make($password),
                'is_active' => ActiveStatus::Active->value,
                'phone' => '0000000000',
            ]);
        } elseif (! Hash::check($password, $admin->password)) {
            // Ensure stored password is hashed
            $admin->password = Hash::make($password);
            $admin->save();
        }
        $auth = AuthFactory::make('sanctum', 'admins');
        $credentials = [
            'email' => $email,
            'password' => $password,
        ];
        $result = $auth->login($credentials);

        return $result['token'] ?? null;
    }
}

if (! function_exists('fakeUserLogin')) {
    /**
     * Authenticate a regular user and return the token.
     */
    function fakeUserLogin($email = ''): ?string
    {
        $email = $email ?: 'user2@demo.com';
        $password = 'password';

        $user = User::withTrashed()
            ->where('email', $email)
            ->first();

        if (! $user) {

            $user = User::create([
                'name' => 'Demo User',
                'email' => $email,
                'phone' => '01211172299',
                'password' => bcrypt($password),
                'is_active' => 1,
            ]);

        } else {

            if ($user->trashed()) {
                $user->restore();
            }

            $user->password = bcrypt($password);
            $user->is_active = true;
            $user->save();
        }

        $auth = AuthFactory::make('sanctum', 'users');

        $credentials = [
            'email' => $email,
            'password' => $password,
        ];

        $result = $auth->login($credentials);

        return $result['token'] ?? null;
    }
}
if (! function_exists('apiKeyGenerator')) {
    function apiKeyGenerator(): string
    {
        $testKey = config('security.test_key');
        $secretTestData = ApiClient::where('name', $testKey)->first();
        $apiKey = $secretTestData->api_key;
        if (! config('security.api_key.encrypted')) {
            return $apiKey;
        }
        $secret = $secretTestData->secret;
        $timestamp = now()->timestamp;
        $data = "{$apiKey}|{$timestamp}";
        $key = substr(hash('sha256', $secret), 0, 32);
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        $combined = $iv.$encrypted;

        return base64_encode($combined);

    }
}
if (! function_exists('signatureKeyGenerator')) {
    function signatureKeyGenerator($method, $data, $fullUrl, $timestamp): string
    {
        $testKey = config('security.test_key');
        $secretTestData = ApiClient::where('name', $testKey)->first();
        $secret = $secretTestData->secret;

        // Ensure URL starts with / if not present
        $fullUrl = str_starts_with($fullUrl, '/') ? $fullUrl : '/'.$fullUrl;
        $fullUrl = config('app.url').$fullUrl;

        // Align body with SignatureMiddleware: empty string for no data or GET
        $body = (is_array($data) && count($data) === 0) ? '' : json_encode($data);

        return SignatureService::generate(method: $method, url: $fullUrl, timestamp: $timestamp, body: $body, secret: $secret);
    }
}
