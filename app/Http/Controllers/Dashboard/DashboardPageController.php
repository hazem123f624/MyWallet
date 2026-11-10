<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Modules\Admins\Models\Admins;

class DashboardPageController extends Controller
{
    public function overview(Request $request): View
    {
        return view('dashboard.overview.index', $this->sharedData($request, 'Dashboard'));
    }

    public function users(Request $request): View
    {
        return view('dashboard.users.index', $this->sharedData($request, 'User Management'));
    }

    public function admins(Request $request): View
    {
        return view('dashboard.admins.index', $this->sharedData($request, 'Admin Management'));
    }

    public function roles(Request $request): View
    {
        return view('dashboard.roles.index', $this->sharedData($request, 'Roles & Permissions'));
    }

    public function models(Request $request): View
    {
        return view('dashboard.models.index', $this->sharedData($request, 'Permission Models'));
    }

    public function activityLogs(Request $request): View
    {
        return view('dashboard.activity-logs.index', $this->sharedData($request, 'Activity Logs'));
    }

    public function transfers(Request $request): View
    {
        return view('dashboard.transfers.index', $this->sharedData($request, 'Transfers'));
    }

    public function beneficiaries(Request $request): View
    {
        return view('dashboard.beneficiaries.index', $this->sharedData($request, 'Beneficiaries'));
    }

    public function profile(Request $request): View
    {
        return view('dashboard.profile.index', $this->sharedData($request, 'Profile'));
    }

    private function sharedData(Request $request, string $title): array
    {
        /** @var Admins $admin */
        $admin = Auth::guard('admins')->user();

        return [
            'title' => $title,
            'apiBaseUrl' => '/api/'.config('api.version'),
            'apiKey' => apiKeyGenerator(),
            'apiToken' => $this->dashboardApiToken($request, $admin),
            'canManageStatus' => $admin->isSuperAdmin(),
            'canManageAdmins' => $admin->isSuperAdmin(),
        ];
    }

    private function dashboardApiToken(Request $request, Admins $admin): string
    {
        $token = $request->session()->get('dashboard_api_token');
        $tokenId = $request->session()->get('dashboard_api_token_id');
        $tokenExists = $tokenId && PersonalAccessToken::find($tokenId);

        if ($token && $tokenExists) {
            return $token;
        }

        $newToken = $admin->createToken('dashboard-web', ['admins']);

        $request->session()->put([
            'dashboard_api_token' => $newToken->plainTextToken,
            'dashboard_api_token_id' => $newToken->accessToken->id,
        ]);

        return $newToken->plainTextToken;
    }
}
