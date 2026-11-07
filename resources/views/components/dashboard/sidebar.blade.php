<aside class="dash-sidebar" id="dashSidebar">
    <div class="dash-sidebar-header">
        <a href="{{ route('dashboard.overview') }}" class="dash-brand">
            <span class="dash-brand-icon"><img src="{{ asset('images/wallet-icon.svg') }}" alt=""></span>
            <span>
                <strong>{{ config('app.name') }}</strong>
                <small>Admin dashboard</small>
            </span>
        </a>

        <button class="dash-icon-button dash-sidebar-close" type="button" data-sidebar-close aria-label="Close navigation">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M6 6l12 12M18 6L6 18" />
            </svg>
        </button>
    </div>

    <nav class="dash-nav" aria-label="Dashboard navigation">
        <a
            href="{{ route('dashboard.overview') }}"
            class="dash-nav-link {{ request()->routeIs('dashboard.overview') ? 'active' : '' }}"
        >
            <x-dashboard.icon name="dashboard" class="dash-nav-icon" />
            <span>Dashboard</span>
        </a>

        @if($canManageAdmins ?? false)
            <p class="dash-nav-label">Administration</p>

            <a
                href="{{ route('dashboard.admins') }}"
                class="dash-nav-link {{ request()->routeIs('dashboard.admins') ? 'active' : '' }}"
            >
                <x-dashboard.icon name="admins" class="dash-nav-icon" />
                <span>Admin Management</span>
            </a>

            <a
                href="{{ route('dashboard.roles') }}"
                class="dash-nav-link {{ request()->routeIs('dashboard.roles') ? 'active' : '' }}"
            >
                <x-dashboard.icon name="roles" class="dash-nav-icon" />
                <span>Roles & Permissions</span>
            </a>

            <a
                href="{{ route('dashboard.models') }}"
                class="dash-nav-link {{ request()->routeIs('dashboard.models') ? 'active' : '' }}"
            >
                <x-dashboard.icon name="models" class="dash-nav-icon" />
                <span>Permission Models</span>
            </a>
        @endif

        <p class="dash-nav-label">Users</p>

        <a
            href="{{ route('dashboard.users') }}"
            class="dash-nav-link {{ request()->routeIs('dashboard.users') ? 'active' : '' }}"
        >
            <x-dashboard.icon name="users" class="dash-nav-icon" />
            <span>User Management</span>
        </a>

        <p class="dash-nav-label">Monitoring</p>

        <a
            href="{{ route('dashboard.activity-logs') }}"
            class="dash-nav-link {{ request()->routeIs('dashboard.activity-logs') ? 'active' : '' }}"
        >
            <x-dashboard.icon name="activity" class="dash-nav-icon" />
            <span>Activity Logs</span>
        </a>

        <p class="dash-nav-label">Account</p>

        <a
            href="{{ route('dashboard.profile') }}"
            class="dash-nav-link {{ request()->routeIs('dashboard.profile') ? 'active' : '' }}"
        >
            <x-dashboard.icon name="profile" class="dash-nav-icon" />
            <span>Profile</span>
        </a>

        <form method="POST" action="{{ route('dashboard.logout') }}" class="dash-nav-form">
            @csrf
            <button type="submit" class="dash-nav-link dash-nav-button">
                <x-dashboard.icon name="logout" class="dash-nav-icon" />
                <span>Logout</span>
            </button>
        </form>
    </nav>

    <div class="dash-sidebar-footer">
        <span>Admin Console</span>
        <a href="{{ url('/docs') }}" target="_blank" rel="noopener">API docs</a>
    </div>
</aside>

<div class="dash-sidebar-backdrop" id="sidebarBackdrop" hidden></div>
