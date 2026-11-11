@props(['title'])

@php
    $user = auth('users')->user();
    $initials = collect(explode(' ', trim($user?->name ?? 'U')))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_substr($part, 0, 1))
        ->implode('');
@endphp

<header class="dash-topbar">
    <button class="dash-icon-button dash-menu-button" type="button" data-user-sidebar-open aria-label="Open navigation">
        <x-dashboard.icon name="menu" />
    </button>

    <nav class="dash-breadcrumb" aria-label="Breadcrumb">
        <a href="{{ route('user.overview') }}">My wallet</a>
        <span class="dash-breadcrumb-separator">/</span>
        <strong>{{ $title }}</strong>
    </nav>

    <div class="dash-topbar-actions">
        <button class="dash-icon-button" type="button" data-theme-toggle aria-label="Toggle dark mode">
            <span data-theme-icon="moon"><x-dashboard.icon name="moon" /></span>
            <span data-theme-icon="sun" hidden><x-dashboard.icon name="sun" /></span>
        </button>

        <div class="dash-user-menu">
            <button class="dash-user-trigger" type="button" data-user-menu-toggle aria-haspopup="true" aria-expanded="false">
                <span class="dash-user-avatar">{{ mb_strtoupper($initials) }}</span>

                <span class="dash-user-meta" title="{{ $user?->phone ?? '' }}">
                    <strong>{{ $user?->name ?? 'User' }}</strong>
                    <span>{{ $user?->phone ?? '' }}</span>
                </span>

                <x-dashboard.icon name="chevron-down" class="dash-user-chevron" />
            </button>

            <div class="dash-user-dropdown" id="userMenu" hidden>
                <a href="{{ route('user.profile') }}">
                    <x-dashboard.icon name="profile" class="dash-nav-icon" />
                    <span>Profile</span>
                </a>

                <a href="{{ route('user.security') }}">
                    <x-dashboard.icon name="shield" class="dash-nav-icon" />
                    <span>Security</span>
                </a>

                <form method="POST" action="{{ route('user.logout') }}">
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
