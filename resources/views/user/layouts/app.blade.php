<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <x-dashboard.head :title="$title" :api-base-url="$apiBaseUrl" :api-key="$apiKey" :api-token="$apiToken" />
    <link rel="stylesheet" href="{{ asset('css/user.css') }}?v={{ filemtime(public_path('css/user.css')) }}">
</head>
<body class="dash-body user-body" data-page="@yield('page', 'user-dashboard')" data-max-sub-wallets="{{ $maxSubWallets ?? 12 }}" data-asset-version="{{ filemtime(public_path('js/user/app.js')) }}">
    <x-user.icon-sprite />

    <a class="dash-skip-link" href="#userMainContent">Skip to content</a>

    <div class="dash-shell">
        <x-user.sidebar />

        <div class="dash-main">
            <x-user.topbar :title="$title" />

            <main class="dash-content" id="userMainContent">
                @yield('content')
            </main>
        </div>
    </div>

    <x-dashboard.modal id="confirmModal" title="Confirm action">
        <p class="dash-modal-text" id="confirmModalMessage"></p>

        <x-slot:footer>
            <button class="dash-button dash-button-ghost" type="button" data-confirm-cancel>Cancel</button>
            <button class="dash-button dash-button-danger" type="button" data-confirm-accept>Confirm</button>
        </x-slot>
    </x-dashboard.modal>

    <div class="dash-toasts" id="toastContainer" aria-live="polite"></div>

    <script src="{{ asset('js/dashboard/vendor/chart.umd.min.js') }}"></script>
    <script type="module" src="{{ asset('js/user/app.js') }}?v={{ filemtime(public_path('js/user/app.js')) }}"></script>
    @stack('scripts')
</body>
</html>
