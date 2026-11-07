<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Traits\HttpResponsesTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardAnalyticsController extends Controller
{
    use HttpResponsesTrait;

    public function overview(Request $request)
    {
        $days = min(90, max(7, (int) $request->query('days', 30)));
        $start = now()->subDays($days - 1)->startOfDay();
        $end = now()->endOfDay();

        $userSeries = DB::table('users')
            ->whereNull('deleted_at')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $activitySeries = DB::table('activity_logs')
            ->where('log_type', 'admin')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $labels = [];
        $newUsers = [];
        $adminActivity = [];

        for ($offset = 0; $offset < $days; $offset++) {
            $date = $start->copy()->addDays($offset);
            $key = $date->toDateString();

            $labels[] = $key;
            $newUsers[] = (int) ($userSeries[$key] ?? 0);
            $adminActivity[] = (int) ($activitySeries[$key] ?? 0);
        }

        $users = DB::table('users');
        $totalUsers = (clone $users)->whereNull('deleted_at')->count();
        $activeUsers = (clone $users)->whereNull('deleted_at')->where('is_active', true)->count();
        $suspendedUsers = (clone $users)->whereNull('deleted_at')->where('is_active', false)->count();
        $deletedUsers = DB::table('users')->whereNotNull('deleted_at')->count();

        $topActions = DB::table('activity_logs')
            ->whereBetween('created_at', [$start, $end])
            ->select('action', DB::raw('COUNT(*) as total'))
            ->groupBy('action')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'action' => $row->action,
                'total' => (int) $row->total,
            ])
            ->values();

        return $this->success(__('messages.dashboard_analytics_retrieved'), data: [
            'range_days' => $days,
            'users' => [
                'total' => $totalUsers,
                'active' => $activeUsers,
                'suspended' => $suspendedUsers,
                'deleted' => $deletedUsers,
                'new' => array_sum($newUsers),
                'series' => [
                    'labels' => $labels,
                    'values' => $newUsers,
                ],
                'status' => [
                    'active' => $activeUsers,
                    'suspended' => $suspendedUsers,
                ],
            ],
            'admins' => [
                'total' => DB::table('admins')->whereNull('deleted_at')->count(),
            ],
            'roles' => [
                'total' => DB::table('roles')->whereNull('deleted_at')->count(),
            ],
            'activity' => [
                'total' => DB::table('activity_logs')->whereBetween('created_at', [$start, $end])->count(),
                'series' => [
                    'labels' => $labels,
                    'values' => $adminActivity,
                ],
                'top_actions' => $topActions,
            ],
            'generated_at' => now()->toIso8601String(),
        ]);
    }
}
