<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\LoginRequest;
use App\Services\Authentication\AuthFactory;
use App\Services\CountryService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;
use Modules\Users\Models\User;

class UserAuthController extends Controller
{
    public function showLogin(Request $request): View|RedirectResponse
    {
        if ($request->boolean('expired')) {
            $this->clearDashboardSession($request);
        }

        if (Auth::guard('users')->check()) {
            return redirect()->route('user.overview');
        }

        return view('user.auth.login', [
            'title' => 'Sign in',
            'apiBaseUrl' => '/api/'.config('api.version'),
            'apiKey' => apiKeyGenerator(),
            'countries' => app(CountryService::class)->getAllCountries(),
        ]);
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $fullPhone = $request->validated('full_phone') ?? $request->validated('phone');

        /** @var User|null $user */
        $user = User::where('phone', $fullPhone)->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'phone' => [__('auth.invalid_credentials')],
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'phone' => [__('users::messages.account_suspended')],
            ]);
        }

        if (! $user->phone_verified_at) {
            throw ValidationException::withMessages([
                'phone' => [__('users::messages.verify_account_first')],
            ]);
        }

        AuthFactory::make('sanctum', 'users')->login([
            'phone' => $fullPhone,
            'password' => $request->validated('password'),
        ], false);

        $request->session()->regenerate();

        /** @var User $user */
        $user = Auth::guard('users')->user();
        $newToken = $user->createToken('user-dashboard-web', ['users']);

        $request->session()->put([
            'user_dashboard_api_token' => $newToken->plainTextToken,
            'user_dashboard_api_token_id' => $newToken->accessToken->id,
        ]);

        return redirect()->intended(route('user.overview'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->clearDashboardSession($request);

        return redirect()->route('user.login');
    }

    private function clearDashboardSession(Request $request): void
    {
        $tokenId = $request->session()->pull('user_dashboard_api_token_id');

        if ($tokenId) {
            PersonalAccessToken::find($tokenId)?->delete();
        }

        Auth::guard('users')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
