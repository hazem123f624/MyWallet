<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\LoginRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;
use Modules\Admins\Models\Admins;

class DashboardAuthController extends Controller
{
    public function showLogin(Request $request): View|RedirectResponse
    {
        if ($request->boolean('expired')) {
            $this->clearDashboardSession($request);
        }

        if (Auth::guard('admins')->check()) {
            return redirect()->route('dashboard.overview');
        }

        return view('dashboard.auth.login', [
            'title' => 'Admin Login',
            'apiBaseUrl' => '/api/'.config('api.version'),
            'apiKey' => apiKeyGenerator(),
        ]);
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();
        $credentials['is_active'] = true;

        if (! Auth::guard('admins')->attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        $request->session()->regenerate();

        /** @var Admins $admin */
        $admin = Auth::guard('admins')->user();
        $newToken = $admin->createToken('dashboard-web', ['admins']);

        $request->session()->put([
            'dashboard_api_token' => $newToken->plainTextToken,
            'dashboard_api_token_id' => $newToken->accessToken->id,
        ]);

        return redirect()->intended(route('dashboard.overview'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->clearDashboardSession($request);

        return redirect()->route('dashboard.login');
    }

    private function clearDashboardSession(Request $request): void
    {
        $tokenId = $request->session()->pull('dashboard_api_token_id');

        if ($tokenId) {
            PersonalAccessToken::find($tokenId)?->delete();
        }

        Auth::guard('admins')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
