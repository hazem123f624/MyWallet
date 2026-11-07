@props(['title'])

@php
    $admin = auth('admins')->user();
    $initials = collect(explode(' ', trim($admin?->name ?? 'A')))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_substr($part, 0, 1))
        ->implode('');
@endphp

<header class="dash-topbar">
    <button class="dash-icon-button dash-menu-button" type="button" data-sidebar-open aria-label="Open navigation">
        <x-dashboard.icon name="menu" />
    </button>

    <nav class="dash-breadcrumb" aria-label="Breadcrumb">
        <a href="{{ route('dashboard.overview') }}">Dashboard</a>
        <span class="dash-breadcrumb-separator">/</span>
        <strong>{{ $title }}</strong>
    </nav>

    <div class="dash-topbar-actions">
        <button class="dash-icon-button" type="button" data-command-open aria-label="Open search">
            <x-dashboard.icon name="search" />
        </button>

        <button class="dash-icon-button" type="button" data-theme-toggle aria-label="Toggle dark mode">
            <span data-theme-icon="moon"><x-dashboard.icon name="moon" /></span>
            <span data-theme-icon="sun" hidden><x-dashboard.icon name="sun" /></span>
        </button>

        <button class="dash-icon-button dash-notification-button" type="button" data-notifications-open aria-label="Open notifications">
            <x-dashboard.icon name="bell" />
            <span class="dash-notification-dot" hidden></span>
        </button>

        <div class="dash-user-menu">
            <button class="dash-user-trigger" type="button" data-user-menu-toggle aria-haspopup="true" aria-expanded="false">
                <span class="dash-user-avatar">{{ mb_strtoupper($initials) }}</span>

                <span class="dash-user-meta" title="{{ $admin?->email ?? '' }}">
                    <strong>{{ $admin?->name ?? 'Administrator' }}</strong>
                    <span>{{ $admin?->isSuperAdmin() ? 'Super Admin' : 'Administrator' }}</span>
                </span>

                <x-dashboard.icon name="chevron-down" class="dash-user-chevron" />
            </button>

            <div class="dash-user-dropdown" id="userMenu" hidden>
                <a href="{{ route('dashboard.profile') }}">
                    <x-dashboard.icon name="profile" class="dash-nav-icon" />
                    <span>Profile</span>
                </a>

                <a href="{{ route('dashboard.activity-logs') }}">
                    <x-dashboard.icon name="activity" class="dash-nav-icon" />
                    <span>My activity</span>
                </a>

                <form method="POST" action="{{ route('dashboard.logout') }}">
                    @csrf
                    <button type="submit">
                        <x-dashboard.icon name="logout" class="dash-nav-icon" />
                        <span>Log out</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
