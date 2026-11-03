<?php

namespace Database\Seeders;

use App\Classes\Authentication\Types\Admins\ForgetPasswordRequest;
use App\Classes\Authentication\Types\Admins\LoginRequest;
use App\Classes\Authentication\Types\Users\RegisterRequest;
use App\Classes\Authentication\Types\Users\LoginRequest as UserLoginRequest;
use App\Classes\Authentication\Types\Users\VerifyOtpRequest as UserVerifyOtpRequest;
use App\Classes\Authentication\Types\Users\ResetPasswordRequest;
use App\Classes\Authentication\Types\Users\VerifyForgotPassword;
use App\Classes\TestCases\TestCaseBuilder;
use App\Services\Documentations\DocumentationService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Admins\Models\Admins;

class AuthSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Ensure base admin exists for documentation tests
        Admins::firstOrCreate(
            ['email' => config('admins.base_admins.0.email')],
            [
                'name' => 'Super Admin',
                'phone' => '01112223334',
                'password' => config('admins.base_admins.0.password'),
                'is_active' => \App\Enums\ActiveStatus::Active,
            ]
        );

        // Clean up registration test user to allow "User Register" documentation case to succeed
        \Modules\Users\Models\User::where('phone', '01011112222')->forceDelete();

        // Ensure standalone verification test user exists
        $user2 = \Modules\Users\Models\User::firstOrCreate(
            ['phone' => '01011113333'],
            [
                'name' => 'Demo User 2',
                'email' => 'user2@example.com',
                'password' => 'P@ssw0rd123',
                'is_active' => \App\Enums\ActiveStatus::Active,
            ]
        );
        // Pre-generate OTP for documentation verification case
        app(\App\Services\OtpService::class)->generate($user2, 'login');

        // Disposable account for the "User Delete Account" documentation case. The case
        // soft deletes the account it authenticates with, so it must never run against a
        // fixture the other cases rely on. It is purged through withTrashed() first,
        // because a plain forceDelete() still respects the soft delete scope and would
        // leave the previous run's row holding the unique phone.
        \Modules\Users\Models\User::withTrashed()->where('phone', '01011119999')->forceDelete();
        $deleteUser = \Modules\Users\Models\User::create([
            'name' => 'Delete User',
            'email' => 'delete_user@example.com',
            'phone' => '01011119999',
            'password' => 'P@ssw0rd123',
            'is_active' => \App\Enums\ActiveStatus::Active,
        ]);

        // Ensure a demo country exists so the "User Register" documentation case can
        // resolve country_code -> country_id (RegisterRequest requires a country in settings).
        $demoCountry = \Modules\Settings\Models\Settings::firstOrCreate(
            [
                'type' => \Modules\Settings\Enums\SettingsType::COUNTRY->value,
                'name_en' => 'Egypt',
            ],
            [
                'name_ar' => 'مصر',
                'is_active' => true,
                'data' => ['code' => 'EG', 'phone_code' => '20', 'phone_length' => 11],
            ]
        );
        // Bust cached country lookups: the file cache persists across migrate:fresh, so a
        // stale entry from a previous run would otherwise point at a dropped settings id.
        \Illuminate\Support\Facades\Cache::forget('country:phone_code:20');
        \Illuminate\Support\Facades\Cache::forget('country:id:' . $demoCountry->id);
        \Illuminate\Support\Facades\Cache::forget('countries:all:active');

        $authDocumentation = [];
        $cases = [
            // Admin Auth
            'Admin Login' => [
                'module' => 'Admins',
                'url' => '/api/'.config('api.version').'/login',
                'method' => 'POST',
                'expect_status' => 200,
                'params' => ['step' => 1, 'email' => config('admins.base_admins.0.email'), 'password' => config('admins.base_admins.0.password')],
                'short_description' => 'Authenticates an administrator and returns an access token.',
                'description' => '',
                'order' => 1,
                'auth' => false,
                'notes' => LoginRequest::class
            ],
            'Forget Password Request' => [
                'module' => 'Admins',
                'url' => '/api/'.config('api.version').'/forget-password',
                'method' => 'POST',
                'expect_status' => 200,
                'params' => ['email' => config('admins.base_admins.0.email')],
                'short_description' => 'Sends an OTP to the user email for password recovery.',
                'description' => '',
                'order' => 2,
                'auth' => false,
                'notes' => ForgetPasswordRequest::class
            ],
            'Verify OTP' => [
                'module' => 'Admins',
                'url' => '/api/'.config('api.version').'/verify-forget-password-otp',
                'method' => 'POST',
                'expect_status' => 200,
                'params' => ['email' => config('admins.base_admins.0.email'), 'otp' => config('auth_guards.test.otp'), 'change_password_token' => config('auth_guards.test.token')],
                'short_description' => 'Verifies the OTP sent to the user email for password recovery.',
                'description' => '',
                'order' => 3,
                'auth' => false,
                'notes' => VerifyForgotPassword::class
            ],
            'Reset Password' => [
                'module' => 'Admins',
                'url' => '/api/'.config('api.version').'/reset-password',
                'method' => 'POST',
                'expect_status' => 200,
                'params' => [
                    'email' => config('admins.base_admins.0.email'),
                    'change_password_token' => config('auth_guards.test.token'),
                    'password' => 'NewP@ssw0rd123',
                    'new_password_confirmation' => 'NewP@ssw0rd123',
                ],
                'short_description' => 'Updates the user password using the provided OTP and temporary token.',
                'description' => '',
                'order' => 4,
                'auth' => false,
                'notes' => ResetPasswordRequest::class
            ],
            'Admin Logout' => [
                'module' => 'Admins',
                'url' => '/api/'.config('api.version').'/logout',
                'method' => 'POST',
                'expect_status' => 200,
                'params' => [],
                'short_description' => 'Revokes the current access token and logs the user out.',
                'description' => '',
                'order' => 5,
                'auth' => true,
            ],

            // User Auth (Mobile)
            'User Register' => [
                'module' => 'Users',
                'url' => '/api/'.config('api.version').'/mobile/users/register',
                'method' => 'POST',
                'expect_status' => 200,
                'params' => [
                    'name' => 'New User',
                    'country_code' => '20',
                    'phone' => '01011112222',
                    'email' => 'user@example.com',
                    'password' => 'P@ssw0rd123',
                    'password_confirmation' => 'P@ssw0rd123',
                ],
                'short_description' => 'Registers a new mobile user and triggers an OTP verification.',
                'description' => 'Initiates registration. User data is temporarily cached until verification.',
                'order' => 6,
                'auth' => false,
                'notes' => RegisterRequest::class
            ],
            'User Verify OTP' => [
                'module' => 'Users',
                'url' => '/api/'.config('api.version').'/mobile/users/verify',
                'method' => 'POST',
                'expect_status' => 200,
                'params' => [
                    'country_code' => '20',
                    'phone' => '01011112222',
                    'otp' => '1234',
                ],
                'short_description' => 'Completes the registration or login flow for mobile users by verifying the OTP.',
                'description' => 'Validates the 6-digit OTP code. For registration, this step persists the user record to the database.',
                'order' => 8,
                'auth' => false,
                'notes' => UserVerifyOtpRequest::class
            ],
            'User Login' => [
                'module' => 'Users',
                'url' => '/api/'.config('api.version').'/mobile/users/login',
                'method' => 'POST',
                'expect_status' => 200,
                'params' => [
                    'country_code' => '20',
                    'phone' => '01011112222',
                    'password' => 'P@ssw0rd123',
                ],
                'short_description' => 'Initializes login for a mobile user by verifying credentials.',
                'description' => 'Validates phone and password. Returns a Bearer token upon success.',
                'order' => 7,
                'auth' => false,
                'notes' => UserLoginRequest::class
            ],
            'User Forget Password' => [
                'module' => 'Users',
                'url' => '/api/'.config('api.version').'/mobile/users/forget-password',
                'method' => 'POST',
                'expect_status' => 200,
                'params' => ['country_code' => '20', 'phone' => '01011112222'],
                'short_description' => 'Sends an OTP to the mobile user phone for password recovery.',
                'description' => 'Step 1 of password reset: Request an OTP for a registered phone number.',
                'order' => 9,
                'auth' => false,
                'notes' => \App\Classes\Authentication\Types\Users\ForgetPasswordRequest::class
            ],
            'User Verify Forgot Password OTP' => [
                'module' => 'Users',
                'url' => '/api/'.config('api.version').'/mobile/users/verify-forget-password',
                'method' => 'POST',
                'expect_status' => 200,
                'params' => [
                    'country_code' => '20',
                    'phone' => '01011112222',
                    'otp' => config('auth_guards.test.otp'),
                    'change_password_token' => config('auth_guards.test.token')
                ],
                'short_description' => 'Verifies the OTP and token for mobile user password recovery.',
                'description' => 'Step 2 of password reset: Verify the OTP received via SMS along with the temporary token.',
                'order' => 10,
                'auth' => false,
                'notes' => VerifyForgotPassword::class
            ],
            'User Reset Password' => [
                'module' => 'Users',
                'url' => '/api/'.config('api.version').'/mobile/users/reset-password',
                'method' => 'POST',
                'expect_status' => 200,
                'params' => [
                    'country_code' => '20',
                    'phone' => '01011112222',
                    'change_password_token' => config('auth_guards.test.token'),
                    'password' => 'NewP@ssw0rd123',
                    'password_confirmation' => 'NewP@ssw0rd123'
                ],
                'short_description' => 'Updates the mobile user password using the provided token.',
                'description' => 'Step 3 of password reset: Finalize the password reset by providing the new password and the validated token.',
                'order' => 11,
                'auth' => false,
                'notes' => ResetPasswordRequest::class
            ],
            'User Change Password' => [
                'module' => 'Users',
                'url' => '/api/'.config('api.version').'/mobile/users/change-password',
                'method' => 'POST',
                'expect_status' => 200,
                'params' => [
                    'old_password' => 'P@ssw0rd123',
                    'new_password' => 'NewP@ssw0rd123',
                    'new_password_confirmation' => 'NewP@ssw0rd123',
                ],
                'short_description' => 'Updates the password for the authenticated mobile user.',
                'description' => 'Requires the current (old) password for verification before updating to the new password.',
                'order' => 11,
                'auth' => true,
                'is_user' => true,
                'notes' => \Modules\Users\Http\Requests\ChangePasswordRequest::class
            ],
            'User Resend OTP' => [
                'module' => 'Users',
                'url' => '/api/'.config('api.version').'/mobile/users/resend-otp',
                'method' => 'POST',
                'expect_status' => 200,
                'params' => ['country_code' => '20', 'phone' => '01011112222'],
                'short_description' => 'Resends a new OTP to the mobile user phone.',
                'description' => 'Requests a fresh OTP code. Checks both registration cache and database state.',
                'order' => 12,
                'auth' => false,
                'notes' => \App\Classes\Authentication\Types\Users\ForgetPasswordRequest::class
            ],
            'User Profile' => [
                'module' => 'Users',
                'url' => '/api/'.config('api.version').'/mobile/users/profile',
                'method' => 'GET',
                'expect_status' => 200,
                'params' => [],
                'short_description' => 'Retrieves the authenticated mobile user profile.',
                'description' => '',
                'order' => 13,
                'auth' => true,
                'is_user' => true,
            ],
            'User Update Profile' => [
                'module' => 'Users',
                'url' => '/api/'.config('api.version').'/mobile/users/profile',
                'method' => 'POST',
                'expect_status' => 200,
                'params' => [
                    'name' => 'Updated User Name',
                    'email' => 'updated_email@example.com',
                ],
                'files' => ['avatar'],
                'short_description' => 'Updates the authenticated mobile user profile with an optional avatar image.',
                'description' => 'Updates the user\'s profile details including name, email, phone, and avatar. Avatar must be a valid image.',
                'order' => 14,
                'auth' => true,
                'is_user' => true,
                'notes' => \Modules\Users\Http\Requests\UpdateProfileRequest::class
            ],
            'User Change Password' => [
                'module' => 'Users',
                'url' => '/api/'.config('api.version').'/mobile/users/change-password',
                'method' => 'POST',
                'expect_status' => 200,
                'params' => [
                    'old_password' => 'P@ssw0rd123',
                    'new_password' => 'NewP@ssw0rd123',
                    'new_password_confirmation' => 'NewP@ssw0rd123',
                ],
                'short_description' => 'Updates the password for the authenticated mobile user.',
                'description' => 'Requires the current (old) password for verification before updating to the new password.',
                'order' => 14.6,
                'auth' => true,
                'is_user' => true,
                'notes' => \Modules\Users\Http\Requests\ChangePasswordRequest::class
            ],
            'User Refresh Token' => [
                'module' => 'Users',
                'url' => '/api/'.config('api.version').'/mobile/users/refresh-token',
                'method' => 'POST',
                'expect_status' => 200,
                'params' => [],
                'short_description' => 'Issues a new session token for the authenticated mobile user.',
                'description' => '',
                'order' => 15,
                'auth' => true,
                'is_user' => true,
            ],
            'User Logout' => [
                'module' => 'Users',
                'url' => '/api/'.config('api.version').'/mobile/users/logout',
                'method' => 'POST',
                'expect_status' => 200,
                'params' => [],
                'short_description' => 'Logs out the authenticated mobile user and revokes the token.',
                'description' => '',
                'order' => 16,
                'auth' => true,
                'is_user' => true,
            ],
            // Must stay the last case: it revokes every token of the account it deletes.
            'User Delete Account' => [
                'module' => 'Users',
                'url' => '/api/'.config('api.version').'/mobile/users/profile',
                'method' => 'DELETE',
                'expect_status' => 200,
                'params' => [],
                'short_description' => 'Deletes the account of the authenticated mobile user.',
                'description' => 'Removes the account of the currently authenticated user. The account is soft deleted and every issued access token is revoked, so the user is no longer treated as an active user and cannot sign in again. A Super Admin can still restore it from the recycle bin.',
                'order' => 17,
                'auth' => true,
                'is_deletable_user' => true,
            ],
        ];

        $authToken = fakeLogin();
        $apiKeyGenerator = apiKeyGenerator();
        foreach ($cases as $key => $case) {
            $timeStamp = time();
            $testCase = TestCaseBuilder::make($key)
                ->{$case['method']}($case['url'])
                ->params($case['params'])
                ->headers([
                    'Content-Type' => 'application/json',
                    'X-API-Token' => $apiKeyGenerator,
                ])
                ->expectStatus($case['expect_status'])
                ->description($case['description'] ?: $case['short_description'])
                ->inDocs($case['short_description'], $case['description'], $case['order']);

            if ($case['auth']) {
                $token = $authToken;
                if ($case['is_user'] ?? false) {
                    $user = \Modules\Users\Models\User::where('phone', '01011112222')->first() ?: \Modules\Users\Models\User::first();
                    $token = $user ? $user->createToken('users-token', ['users'])->plainTextToken : $authToken;
                }
                if ($case['is_deletable_user'] ?? false) {
                    $token = $deleteUser->createToken('users-token', ['users'])->plainTextToken;
                }
                $testCase = $testCase->auth($token);
            }

            if (array_key_exists('notes', $case)) {
                $notes = app(DocumentationService::class)->generateValidationDocs($case['notes']);
                $testCase = $testCase->notes($notes);
            }
            $testCase = $testCase->build();
            $authDocumentation[] = [
                'module' => $case['module'],
                'data' => $testCase
            ];
        }

        // Run the builder results through the TestCasesModule to save them to the documentations table.
        foreach ($authDocumentation as $doc) {
            (new \App\Classes\TestCases\TestCasesModule(
                module: $doc['module'],
                testCase: 'Authentication',
                files: [],
                testCasesData: ['Authentication' => [$doc['data']]]
            ))->handle();
        }
    }
}
