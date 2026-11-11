<?php

namespace Modules\Users\Http\Controllers\Mobile;

use App\Classes\Authentication\AuthController;
use App\Http\Traits\HttpResponsesTrait;
use App\Models\Otp;
use App\Services\CountryService;
use App\Services\OtpService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Modules\Users\Events\UserAccountVerified;
use Modules\Users\Http\Requests\ChangePasswordRequest;
use Modules\Users\Http\Requests\DeleteAccountRequest;
use Modules\Users\Http\Requests\UpdateProfileRequest;
use Modules\Users\Interfaces\UsersInterface;
use Modules\Users\Models\User;
use Modules\Users\Resources\UserResource;

class UsersController extends AuthController
{
    use HttpResponsesTrait;

    /** @var User */
    protected $model;

    private UsersInterface $repository;

    protected $registerRequest;

    protected $loginRequest;

    protected $verifyLoginRequest;

    public function __construct(UsersInterface $repository)
    {
        // Using 'users' auth type, 'sanctum' method, and Single Factor Auth (singleFactorAuth = true)
        // because the user wants Login -> Token immediately.
        parent::__construct(
            authFactoryType: 'users',
            authFactoryMethod: 'sanctum',
            singleFactorAuth: true,
            loginType: 'phone',
            forgetPasswordType: 'phone',
            verifyType: 'login'
        );
        $this->repository = $repository;
    }

    /**
     * Signup a new user.
     */
    public function register()
    {
        $request = app($this->registerRequest);
        $request->validateResolved();

        $validatedData = $request->validated();
        $phone = $validatedData['full_phone'] ?? $validatedData['phone'];
        $validatedData['phone'] = $phone;
        unset($validatedData['full_phone']);
        $countryId = $validatedData['country_id'];

        // Check if user exists
        if (User::where('phone', $phone)->exists()) {
            return $this->failure(__('users::messages.phone_already_registered'), 422);
        }
        $uniqueIdentifier = $phone;

        // Use OtpService to handle caching, hashing, and cooldowns
        $code = app(OtpService::class)->generateByCache(
            identifier: $uniqueIdentifier,
            type: 'registration',
            extraData: $validatedData
        );

        return $this->success(__('users::messages.registration_otp_sent'), data: [
            'otp' => config('app.env') !== 'production' ? $code : null,
        ]);
    }

    /**
     * Login override to check verification status.
     */
    public function login()
    {
        $request = app($this->loginRequest);

        $request->validateResolved();
        $credentials = $request->only($this->loginType, 'password');

        if ($request->filled('full_phone')) {
            $credentials['phone'] = $request->input('full_phone');
        }

        // Check if user exists and is verified
        /** @var User|null $user */
        $user = ($this->model)::where('phone', $credentials['phone'])->first();
        if ($user && ! $user->is_active) {
            return $this->failure(__('users::messages.account_suspended'), 403);
        }

        if ($user && ! $user->phone_verified_at) {
            return $this->failure(__('users::messages.verify_account_first'), 403);
        }

        return parent::login();
    }

    /**
     * Get current authenticated user profile.
     */
    public function profile()
    {
        /** @var User|null $user */
        $user = auth('sanctum')->user();
        if (! $user) {
            return $this->failure('Unauthorized', 401);
        }

        return $this->success(__('users::messages.profile_retrieved'), data: new UserResource($user));
    }

    /**
     * Update current authenticated user profile.
     */
    public function updateProfile(UpdateProfileRequest $request)
    {
        /** @var User|null $user */
        $user = auth('sanctum')->user();
        if (! $user) {
            return $this->failure('Unauthorized', 401);
        }

        $request->validateResolved();
        $validated = $request->validated();

        // Handle phone change verification
        $hasPhoneChange = false;
        $newPhone = null;

        if (array_key_exists('phone', $validated)) {
            $newPhone = $validated['full_phone'] ?? $validated['phone'];
            if ($newPhone !== $user->phone) {
                $hasPhoneChange = true;
            } else {
                // If phone didn't actually change, we don't need to re-verify. Remove it to not trigger phone_change logic.
                unset($validated['phone']);
                unset($validated['full_phone']);
                unset($validated['country_code']);
                unset($validated['country_id']);
            }
        }

        if ($hasPhoneChange) {
            // Send OTP to the NEW phone number
            $code = app(OtpService::class)->generate($user, 'phone_change', $newPhone);

            // Keep the current phone and country until the new number is verified.
            unset($validated['phone']);
            unset($validated['full_phone']);
            unset($validated['country_code']);
            unset($validated['country_id']);

            $this->repository->save($validated, $user->id);

            $userData = (new UserResource($user->fresh()))->resolve();

            return $this->success(__('users::messages.profile_updated_phone_pending'), data: array_merge($userData, [
                'otp_code' => config('app.env') !== 'production' ? $code : null,
                'needs_phone_verification' => true,
                'pending_phone' => $newPhone,
            ]));
        }

        // Regular update, cleanup non-existent DB columns
        unset($validated['full_phone']);
        unset($validated['country_code']);

        // Regular update
        $updatedUser = $this->repository->save($validated, $user->id);

        return $this->success(__('users::messages.profile_updated'), data: new UserResource($updatedUser));
    }

    /**
     * Delete the current authenticated user account (Soft Delete).
     */
    public function destroy(DeleteAccountRequest $request)
    {
        /** @var User|null $user */
        $user = auth('sanctum')->user();
        if (! $user) {
            return $this->failure(__('messages.unauthorized'), 401);
        }

        try {
            // Revoke the tokens before the soft delete: the auth factory looks
            // the user up without the trashed records, so it finds nobody then.
            $this->authFactory->logout(ids: [$user->id]);
            $this->repository->delete($user);
        } catch (\Exception $exception) {
            return $this->failure(__('messages.server_error'), 500);
        }

        return $this->success(__('users::messages.account_deleted'), data: []);
    }

    /**
     * Resend OTP, handling both cached registration and database users.
     */
    public function resendOtp()
    {
        $phone = request($this->loginType);
        if (request()->has('country_code') && $phone) {
            $phone = app(CountryService::class)->getFullPhone($phone, request('country_code'));
            request()->merge(['full_phone' => $phone]);
        }

        if (! $phone) {
            return $this->failure(__('users::messages.phone_required'), 422);
        }

        // 1. Check if user is in registration cache (via OtpService naming convention)
        if (Cache::has("otp:cache:registration:{$phone}")) {
            $code = app(OtpService::class)->generateByCache($phone, 'registration');

            return $this->success(__('users::messages.otp_resent_registration'), data: [
                'otp' => config('app.env') !== 'production' ? $code : null,
            ]);
        }

        // 2. Fallback to standard DB resend
        return parent::resendOtp();
    }

    /**
     * Send OTP for forgotten password (Step 1).
     * Overridden to include OTP in response for development environments.
     */
    public function forgetPassword()
    {
        $request = app($this->forgetPasswordRequest);
        $request->validateResolved();
        $phone = $request->input('full_phone') ?? $request->phone;

        /** @var User $user */
        $user = ($this->model)::where('phone', $phone)->firstOrFail();

        // Use the authFactory to generate the token and send the SMS/OTP
        $data = $request->validated();
        $data['phone'] = $phone;
        $token = $this->authFactory->forgotPassword(request: $data, type: $this->forgetPasswordType);

        // Get the latest OTP for this user to return it in dev
        $otp = $user->otps()->where('type', 'forgot_password')->latest()->first()?->code;

        return $this->success(__('users::messages.otp_reset_sent'), data: [
            'token' => $token,
            'otp' => config('app.env') !== 'production' ? '1234' : null,
        ]);
    }

    /**
     * resetPassword and verifyForgotPassword will be handled by the parent AuthController
     * which correctly uses the token and OptService.
     */

    /**
     * Change password for logged-in user.
     */
    public function changePassword(ChangePasswordRequest $request)
    {
        /** @var User|null $user */
        $user = auth('sanctum')->user();
        if (! $user || ! Hash::check($request->old_password, $user->password)) {
            return $this->failure(__('users::messages.password_incorrect'), 422);
        }

        $user->update(['password' => $request->new_password]);

        return $this->success(__('users::messages.password_changed'));
    }

    /**
     * Verify OTP for account verification (new user) or phone change.
     */
    public function verifyAccount()
    {

        $request = app($this->verifyLoginRequest);
        $request->validateResolved();
        $payload = $request->validated();
        $phone = $payload['full_phone'] ?? $payload['phone'];
        $otp = $payload['otp'];

        // Case 1: Registration Verification (Pending in Cache)
        if (Cache::has("otp:cache:registration:{$phone}")) {

            $data = app(OtpService::class)->verifyByCache($phone, $otp, 'registration');

            $data['phone'] = $data['full_phone'] ?? $data['phone'];
            unset($data['full_phone']);
            $data['phone_verified_at'] = now();
            $user = $this->repository->save($data);

            event(new UserAccountVerified(
                userId: $user->id,
                countryId: $user->country_id ? (int) $user->country_id : null
            ));

            $token = $user->createToken('auth-token')->plainTextToken;

            $userResource = new UserResource($user);
            $userResource->token = $token;
            $userResource->token_type = 'Bearer';
            $userResource->expires_in = null;

            return $this->success(__('users::messages.account_verified'), data: $userResource);
        }

        // Case 2: Existing User (Phone Change or already saved unverified user)
        $user = ($this->model)::where('phone', $phone)->first();

        if (! $user) {
            // Try to find user via pending OTP (useful for phone changes)
            $otpRecord = Otp::where('identifier', $phone)
                ->where('is_verified', false)
                ->latest()
                ->first();

            if ($otpRecord) {
                $user = $otpRecord->otpable;
            }
        }

        if (! $user && auth('sanctum')->check()) {
            $user = auth('sanctum')->user();
        }

        if (! $user) {
            return $this->failure(__('users::messages.invalid_otp'), 422);
        }

        /** @var User $user */
        $hasPhoneChangeOtp = $user->otps()->where('type', 'phone_change')->where('is_verified', false)->exists();
        $type = $hasPhoneChangeOtp ? 'phone_change' : $this->verifyType;

        $authData = app(OtpService::class)->verify(model: $user, code: $otp, type: $type, withToken: true);

        if ($type === 'phone_change') {
            $otpRecord = $user->otps()->where('type', 'phone_change')->where('is_verified', true)->latest()->first();
            $newPhone = $otpRecord->identifier;
            $country = app(CountryService::class)->getCountryByFullPhone($newPhone);

            $user->update([
                'phone' => $newPhone,
                'country_id' => $country?->id ?? $user->country_id,
            ]);
            $message = __('users::messages.phone_updated');
        } else {
            $user->update(['phone_verified_at' => now()]);

            event(new UserAccountVerified(
                userId: $user->id,
                countryId: $user->country_id ? (int) $user->country_id : null
            ));

            $message = __('users::messages.account_activated');
        }

        $userResource = new UserResource($user->fresh());
        $userResource->token = $authData['token'] ?? null;
        $userResource->token_type = $authData['token_type'] ?? 'Bearer';
        $userResource->expires_in = $authData['expires_in'] ?? null;

        return $this->success($message, data: $userResource);
    }
}
