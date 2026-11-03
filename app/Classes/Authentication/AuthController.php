<?php

namespace App\Classes\Authentication;

use App\Http\Controllers\Controller;
use App\Http\Traits\HttpResponsesTrait;
use App\Services\Authentication\AuthFactory;
use App\Services\Authentication\AuthInterface;
use App\Services\OtpService;
use Illuminate\Validation\ValidationException;

use Modules\Admins\DTOs\AdminsDTO;
use Modules\Admins\Models\Admins;
use Modules\Admins\Resources\AdminResource;

class AuthController extends Controller
{
    use HttpResponsesTrait;
    private bool $singleFactorAuth;
    protected AuthInterface $authFactory;
    public String $loginType;
    public String $forgetPasswordType;
    public String $verifyType;
    protected $loginRequest;
    protected $forgetPasswordRequest;
    protected $resetPasswordRequest;
    protected $verifyForgotPasswordRequest;
    protected $verifyLoginRequest;
    protected $dto;
    protected $model;
    protected $resource;

    public function __construct(string $authFactoryType,string $authFactoryMethod='sanctum', bool $singleFactorAuth=true,string $loginType='email',string $forgetPasswordType='email', string $verifyType='login')
    {
        $this->loginType = $loginType;
        $this->forgetPasswordType = $forgetPasswordType;
        $this->verifyType = $verifyType;
        $this->singleFactorAuth = $singleFactorAuth;
        $this->authFactory = AuthFactory::make($authFactoryMethod, $authFactoryType);
        $this->resolveModule($authFactoryType);

    }

    private function resolveModule(string $type): void
    {
        $map = [
            'admins' => [
                'loginRequest' => \App\Classes\Authentication\Types\Admins\LoginRequest::class,
                'forgetPasswordRequest' => \App\Classes\Authentication\Types\Admins\ForgetPasswordRequest::class,
                'resetPasswordRequest' => \App\Classes\Authentication\Types\Admins\ResetPasswordRequest::class,
                'verifyForgotPasswordRequest' => \App\Classes\Authentication\Types\Admins\VerifyForgotPassword::class,
                'dto' => AdminsDTO::class,
                'model' => Admins::class,
                'resource' => \Modules\Admins\Resources\AdminLoginResource::class,
            ],
            'users' => [
                'loginRequest' => \App\Classes\Authentication\Types\Users\LoginRequest::class,
                'registerRequest' => \App\Classes\Authentication\Types\Users\RegisterRequest::class,
                'verifyLoginRequest' => \App\Classes\Authentication\Types\Users\VerifyOtpRequest::class,
                'forgetPasswordRequest' => \App\Classes\Authentication\Types\Users\ForgetPasswordRequest::class, // Need to create or reuse
                'resetPasswordRequest' => \App\Classes\Authentication\Types\Users\ResetPasswordRequest::class,   // Need to create or reuse
                'verifyForgotPasswordRequest' => \App\Classes\Authentication\Types\Users\VerifyForgotPassword::class, // Need to create or reuse
                'dto' => \Modules\Users\DTOs\UsersDTO::class,
                'model' => \Modules\Users\Models\User::class,
                'resource' => \Modules\Users\Resources\UserResource::class,
            ],
        ];

        if (!isset($map[$type])) {
            throw new \InvalidArgumentException("Invalid auth type: {$type}");
        }

        foreach ($map[$type] as $key => $value) {
            $this->{$key} = $value;
        }
    }

    public function login()
    {
        $request = app($this->loginRequest);
        $request->validateResolved();
        $credentials = $request->only($this->loginType, 'password');
        $authData = $this->authFactory->login(request: $credentials, createToken: $this->singleFactorAuth);

        $resource = new $this->resource($authData['user']);
        if ($this->singleFactorAuth) {
            $resource->token = $authData['token'] ?? null;
            $resource->token_type = $authData['token_type'] ?? 'Bearer';
            $resource->expires_in = $authData['expires_in'] ?? null;
        }

        return $this->success(__('messages.welcome') . ' ' . ($resource->name ?? ''), data: $resource);
    }

    public function verifyLoginOtp()
    {
        $request = app($this->verifyLoginRequest);
        $request->validateResolved();

        $request = $request->validated();
        $user = ($this->model)::where('phone', $request['phone'])->firstOrFail();
        $authData = app(OtpService::class)->verify(model: $user, code: $request['otp'], type: $this->verifyType, withToken: true);
        $resource = new $this->resource(($this->dto)::fromModel($user));

        $resource->token = $authData['token'] ?? null;
        $resource->token_type = $authData['token_type'] ?? 'Bearer';
        $resource->expires_in = $authData['expires_in'] ?? null;

        return $this->success(__('messages.welcome').($resource->name ?? ''), data: $resource);
    }

    public function resendOtp()
    {
        $request = request()->all();
        $identifier = $request['full_phone'] ?? $request[$this->loginType] ?? null;

        if (!$identifier) {
            return $this->failure(__('messages.identifier_is_required'), 422);
        }

        $type = request('type', $this->verifyType);
        $code = null;
        $user = null;

        // 1. Check if the OTP is managed in Cache (e.g., Registration)
        if (\Illuminate\Support\Facades\Cache::has("otp:cache:{$type}:{$identifier}")) {
            $code = app(\App\Services\OtpService::class)->generateByCache($identifier, $type);
        } else {
            // 2. Fallback to Database for persisted users (e.g., Login, Forgot Password)
            $user = ($this->model)::where($this->loginType, $identifier)->firstOrFail();
            $code = app(\App\Services\OtpService::class)->generate(model: $user, type: $type);
        }

        return $this->success(__('messages.otp_resent_successfully'), data: [
            'otp' => config('app.env') !== 'production' ? $code : null
        ]);
    }

    public function refreshToken()
    {
        $data = $this->authFactory->refreshToken();
        return $this->success('New Token', data: $data);
    }

    public function logout()
    {
        $this->authFactory->logout();
        return $this->success('Good by.', data: []);
    }

    public function forgetPassword()
    {
        $request = app($this->forgetPasswordRequest);
        $request->validateResolved();
        $request = $request->validated();
        $token = $this->authFactory->forgotPassword(request: $request, type: $this->forgetPasswordType);
        return $this->success('Please check your '.$this->forgetPasswordType, data: [
            'change_password_token' => $token,
        ]);
    }

    public function verifyForgotPassword()
    {

        $request = app($this->verifyForgotPasswordRequest);
        $request->validateResolved();
        $request = $request->validated();

        $user = ($this->model)::where($this->forgetPasswordType, $request[$this->forgetPasswordType])->firstOrFail();
        if ($user->change_password_token != $request['change_password_token'] || $user->change_password_expired_at->isPast()) {
            throw ValidationException::withMessages([
                'change_password_token' => [__('Invalid or expired token')],
            ]);
        }
        app(OtpService::class)->verify($user, $request['otp'], 'forgot_password');
        return $this->success('Your Otp Verified successfully.', data: [
            'change_password_token' => $user->change_password_token,
        ]);
    }

    public function resetPassword()
    {
        $request = app($this->resetPasswordRequest);
        $request->validateResolved();
        $request = $request->validated();

        $this->authFactory->resetPassword(request: $request, type: $this->forgetPasswordType);
        return $this->success('Password reset successfully.', data: []);
    }
}
