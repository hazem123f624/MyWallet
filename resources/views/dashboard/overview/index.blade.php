@extends('dashboard.layouts.app')

@section('page', 'overview')

@section('content')
    <x-dashboard.page-header
        title="Dashboard"
        description="A quick overview of the platform, growth, and recent account activity."
    >
        <x-slot:actions>
            <span class="dash-refresh-meta">
                Updated <span id="overviewRefreshedAt">—</span>
            </span>

            <button class="dash-button dash-button-ghost" type="button" data-overview-refresh>
                Refresh
            </button>

            <a class="dash-button dash-button-ghost" href="{{ route('dashboard.users') }}">Manage users</a>
        </x-slot:actions>
    </x-dashboard.page-header>

    <section class="dash-stats" aria-label="Platform statistics">
        <x-dashboard.stat-card label="Total users" value-id="overviewTotalUsers" tone="brand" hint="Registered accounts" icon="users" />
        <x-dashboard.stat-card label="Active users" value-id="overviewActiveUsers" tone="success" hint="Can access the app" icon="check" />
        <x-dashboard.stat-card label="Suspended users" value-id="overviewSuspendedUsers" tone="warning" hint="Access is blocked" icon="warning" />
        <x-dashboard.stat-card label="Total admins" value-id="overviewTotalAdmins" tone="danger" hint="Dashboard administrators" icon="admins" />
    </section>

    <div class="dash-chart-grid">
        <section class="dash-panel dash-chart-panel">
            <div class="dash-panel-heading">
                <div>
                    <h2>User growth</h2>
                    <p>New accounts created in the selected period.</p>
                </div>

                <span class="dash-badge dash-badge-neutral" id="overviewNewUsers">0 new</span>
            </div>

            <div class="dash-chart-wrap">
                <canvas id="overviewUserChart" aria-label="User growth chart" role="img"></canvas>
            </div>
        </section>

        <section class="dash-panel dash-chart-panel dash-status-panel">
            <div class="dash-panel-heading">
                <div>
                    <h2>Account status</h2>
                    <p>Active versus suspended users.</p>
                </div>

                <span class="dash-badge dash-badge-neutral" id="overviewStatusTotalBadge">—</span>
            </div>

            <div class="dash-status-body">
                <div class="dash-status-loading" id="overviewStatusLoading">
                    <span class="dash-skeleton dash-status-skeleton"></span>
                    <span class="dash-skeleton dash-status-skeleton-line"></span>
                    <span class="dash-skeleton dash-status-skeleton-line short"></span>
                </div>

                <div class="dash-status-empty" id="overviewStatusEmpty" hidden>
                    <span class="dash-status-empty-icon">
                        <x-dashboard.icon name="info" />
                    </span>
                    <strong id="overviewStatusEmptyTitle">No status data available</strong>
                    <span id="overviewStatusEmptyText">User status statistics will appear here once accounts are created.</span>
                </div>

                <div class="dash-status-content" id="overviewStatusContent" hidden>
                    <div class="dash-donut-wrap">
                        <canvas id="overviewStatusChart" aria-label="User status chart" role="img"></canvas>
                        <div class="dash-donut-center">
                            <strong id="overviewStatusTotal">0</strong>
                            <span>Total users</span>
                        </div>
                    </div>

                    <div class="dash-status-legend">
                        <div class="dash-status-item dash-status-item-active">
                            <span class="dash-status-dot"></span>
                            <span class="dash-status-copy">
                                <span>Active</span>
                                <small id="overviewStatusActivePercent">0%</small>
                            </span>
                            <strong id="overviewStatusActiveCount">0</strong>
                        </div>

                        <div class="dash-status-item dash-status-item-suspended">
                            <span class="dash-status-dot"></span>
                            <span class="dash-status-copy">
                                <span>Suspended</span>
                                <small id="overviewStatusSuspendedPercent">0%</small>
                            </span>
                            <strong id="overviewStatusSuspendedCount">0</strong>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div class="dash-overview-grid">
        <section class="dash-panel">
            <div class="dash-panel-heading">
                <div>
                    <h2>Recent users</h2>
                    <p>The newest accounts on the platform.</p>
                </div>

                <a class="dash-text-link" href="{{ route('dashboard.users') }}">View all</a>
            </div>

            <div class="dash-table-wrap">
                <table class="dash-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Status</th>
                            <th class="dash-col-date">Created</th>
                        </tr>
                    </thead>
                    <tbody id="overviewRecentUsers"></tbody>
                </table>

                <div class="dash-table-loading" id="overviewLoading" hidden>
                    <span class="dash-spinner"></span>
                    <span>Loading overview...</span>
                </div>

                <x-dashboard.empty-state
                    id="overviewEmpty"
                    title="No users yet"
                    message="New accounts will appear here."
                />
            </div>
        </section>

        <aside class="dash-panel dash-quick-panel">
            <div class="dash-panel-heading">
                <div>
                    <h2>Quick actions</h2>
                    <p>Common administration shortcuts.</p>
                </div>
            </div>

            <div class="dash-quick-links">
                <a href="{{ route('dashboard.users') }}">
                    <span>
                        <strong>User Management</strong>
                        <small>Review and control user accounts</small>
                    </span>
                    <span aria-hidden="true">→</span>
                </a>

                @if($canManageAdmins ?? false)
                    <a href="{{ route('dashboard.admins') }}">
                        <span>
                            <strong>Admin Management</strong>
                            <small>Manage dashboard administrators</small>
                        </span>
                        <span aria-hidden="true">→</span>
                    </a>

                    <a href="{{ route('dashboard.roles') }}">
                        <span>
                            <strong>Roles & Permissions</strong>
                            <small>Review access and role membership</small>
                        </span>
                        <span aria-hidden="true">→</span>
                    </a>
                @endif

                <a href="{{ route('dashboard.activity-logs') }}">
                    <span>
                        <strong>Activity Logs</strong>
                        <small>Inspect the platform audit trail</small>
                    </span>
                    <span aria-hidden="true">→</span>
                </a>
            </div>
        </aside>
    </div>

    <section class="dash-panel" style="margin-top: 18px;">
        <div class="dash-panel-heading">
            <div>
                <h2>Recent activity</h2>
                <p>The latest actions recorded across the platform.</p>
            </div>

            <a class="dash-text-link" href="{{ route('dashboard.activity-logs') }}">View all</a>
        </div>

        <div class="dash-overview-activity" id="overviewRecentActivity"></div>
    </section>
@endsection
