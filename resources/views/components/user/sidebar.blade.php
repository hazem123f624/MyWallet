@php
    $user = auth('users')->user();
    $initials = collect(explode(' ', trim($user?->name ?? 'U')))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_substr($part, 0, 1))
        ->implode('');
@endphp

<aside class="dash-sidebar user-sidebar" id="userSidebar">
    <div class="dash-sidebar-header">
        <a href="{{ route('user.overview') }}" class="dash-brand">
            <span class="dash-brand-icon"><img src="{{ asset('images/wallet-icon.svg') }}" alt=""></span>
            <span>
                <strong>{{ config('app.name') }}</strong>
                <small>My wallet</small>
            </span>
        </a>

        <button class="dash-icon-button dash-sidebar-close" type="button" data-user-sidebar-close aria-label="Close navigation">
            <x-dashboard.icon name="close" />
        </button>
    </div>

    <nav class="dash-nav" aria-label="Wallet navigation">
        <a href="{{ route('user.overview') }}" class="dash-nav-link {{ request()->routeIs('user.overview') ? 'active' : '' }}">
            <x-dashboard.icon name="dashboard" class="dash-nav-icon" />
            <span>Overview</span>
        </a>

        <p class="dash-nav-label">Money</p>

        <a href="{{ route('user.wallets') }}" class="dash-nav-link {{ request()->routeIs('user.wallets') ? 'active' : '' }}">
            <x-dashboard.icon name="wallet" class="dash-nav-icon" />
            <span>Wallets</span>
        </a>

        <a href="{{ route('user.transfers') }}" class="dash-nav-link {{ request()->routeIs('user.transfers') ? 'active' : '' }}">
            <x-dashboard.icon name="send" class="dash-nav-icon" />
            <span>Send Money</span>
        </a>

        <a href="{{ route('user.transactions') }}" class="dash-nav-link {{ request()->routeIs('user.transactions') ? 'active' : '' }}">
            <x-dashboard.icon name="activity" class="dash-nav-icon" />
            <span>Transactions</span>
        </a>

        <a href="{{ route('user.beneficiaries') }}" class="dash-nav-link {{ request()->routeIs('user.beneficiaries') ? 'active' : '' }}">
            <x-dashboard.icon name="users" class="dash-nav-icon" />
            <span>Beneficiaries</span>
        </a>

        <a href="{{ route('user.bank-accounts') }}" class="dash-nav-link {{ request()->routeIs('user.bank-accounts') ? 'active' : '' }}">
            <x-dashboard.icon name="bank" class="dash-nav-icon" />
            <span>Bank Accounts</span>
        </a>

        <p class="dash-nav-label">Account</p>

        <a href="{{ route('user.profile') }}" class="dash-nav-link {{ request()->routeIs('user.profile') ? 'active' : '' }}">
            <x-dashboard.icon name="profile" class="dash-nav-icon" />
            <span>Profile</span>
        </a>

        <a href="{{ route('user.security') }}" class="dash-nav-link {{ request()->routeIs('user.security') ? 'active' : '' }}">
            <x-dashboard.icon name="shield" class="dash-nav-icon" />
            <span>Security</span>
        </a>

        <form method="POST" action="{{ route('user.logout') }}" class="dash-nav-form">
            @csrf
            <button type="submit" class="dash-nav-link dash-nav-button">
                <x-dashboard.icon name="logout" class="dash-nav-icon" />
                <span>Logout</span>
            </button>
        </form>
    </nav>

    <div class="dash-sidebar-footer">
        <span>Personal wallet</span>
        <a href="{{ url('/docs') }}" target="_blank" rel="noopener">API docs</a>
    </div>
</aside>

<div class="dash-sidebar-backdrop" id="userSidebarBackdrop" hidden></div>
