<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <x-dashboard.head :title="$title" :api-base-url="$apiBaseUrl" :api-key="$apiKey" :api-token="$apiToken" />
</head>
<body class="dash-body" data-page="@yield('page', 'dashboard')" data-can-manage-status="{{ ($canManageStatus ?? false) ? '1' : '0' }}">
    <a class="dash-skip-link" href="#mainContent">Skip to content</a>

    <div class="dash-shell">
        <x-dashboard.sidebar />

        <div class="dash-main">
            <x-dashboard.topbar :title="$title" />

            <main class="dash-content" id="mainContent">
                @yield('content')
            </main>
        </div>
    </div>

    <div class="dash-drawer-backdrop" id="notificationBackdrop" hidden data-notifications-close></div>

    <aside class="dash-drawer" id="notificationDrawer" aria-label="Notifications" hidden>
        <header class="dash-drawer-header">
            <div>
                <h2>Notifications</h2>
                <p>Recent platform activity.</p>
            </div>

            <button class="dash-icon-button" type="button" data-notifications-close aria-label="Close notifications">
                <x-dashboard.icon name="close" />
            </button>
        </header>

        <div class="dash-drawer-body" id="notificationList">
            <div class="dash-drawer-loading">
                <span class="dash-spinner"></span>
                <span>Loading activity...</span>
            </div>
        </div>

        <footer class="dash-drawer-footer">
            <a class="dash-text-link" href="{{ route('dashboard.activity-logs') }}">View all activity</a>
        </footer>
    </aside>

    <x-dashboard.modal id="confirmModal" title="Confirm action">
        <p class="dash-modal-text" id="confirmModalMessage"></p>

        <x-slot:footer>
            <button class="dash-button dash-button-ghost" type="button" data-confirm-cancel>Cancel</button>
            <button class="dash-button dash-button-danger" type="button" data-confirm-accept>Confirm</button>
        </x-slot>
    </x-dashboard.modal>

    <div class="dash-command" id="commandPalette" hidden>
        <div class="dash-command-backdrop" data-command-close></div>

        <div class="dash-command-panel" role="dialog" aria-modal="true" aria-label="Global search">
            <div class="dash-command-input">
                <x-dashboard.icon name="search" />
                <input id="commandInput" type="search" placeholder="Search pages, users, admins, roles..." autocomplete="off">
                <kbd>Esc</kbd>
            </div>

            <div class="dash-command-results" id="commandResults"></div>
        </div>
    </div>

    <div class="dash-toasts" id="toastContainer" aria-live="polite"></div>

    <script src="{{ asset('js/dashboard/vendor/chart.umd.min.js') }}"></script>
    <script type="module" src="{{ asset('js/dashboard/app.js') }}?v={{ filemtime(public_path('js/dashboard/app.js')) }}"></script>
    @stack('scripts')
</body>
</html>
