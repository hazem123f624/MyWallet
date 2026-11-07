<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\CountryService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Modules\Users\Models\User;

class UserPageController extends Controller
{
    public function overview(Request $request): View
    {
        return view('user.overview.index', $this->sharedData($request, 'Overview'));
    }

    public function wallets(Request $request): View
    {
        return view('user.wallets.index', $this->sharedData($request, 'Wallets'));
    }

    public function bankAccounts(Request $request): View
    {
        return view('user.bank-accounts.index', $this->sharedData($request, 'Bank Accounts'));
    }

    public function profile(Request $request): View
    {
        return view('user.profile.index', $this->sharedData($request, 'Profile') + [
            'countries' => app(CountryService::class)->getAllCountries(),
        ]);
    }

    public function security(Request $request): View
    {
        return view('user.security.index', $this->sharedData($request, 'Security'));
    }

    private function sharedData(Request $request, string $title): array
    {
        /** @var User $user */
        $user = Auth::guard('users')->user();

        return [
            'title' => $title,
            'apiBaseUrl' => '/api/'.config('api.version'),
            'apiKey' => apiKeyGenerator(),
            'apiToken' => $this->dashboardApiToken($request, $user),
            'subWalletColors' => config('wallets.sub_wallet_colors', []),
            'subWalletIcons' => config('wallets.sub_wallet_icons', []),
            'maxSubWallets' => (int) config('wallets.max_sub_wallets', 12),
        ];
    }

    private function dashboardApiToken(Request $request, User $user): string
    {
        $token = $request->session()->get('user_dashboard_api_token');
        $tokenId = $request->session()->get('user_dashboard_api_token_id');
        $tokenExists = $tokenId && PersonalAccessToken::find($tokenId);

        if ($token && $tokenExists) {
            return $token;
        }

        $newToken = $user->createToken('user-dashboard-web', ['users']);

        $request->session()->put([
            'user_dashboard_api_token' => $newToken->plainTextToken,
            'user_dashboard_api_token_id' => $newToken->accessToken->id,
        ]);

        return $newToken->plainTextToken;
    }
}
