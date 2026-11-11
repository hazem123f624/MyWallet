<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <x-dashboard.head :title="$title" :api-base-url="$apiBaseUrl ?? null" :api-key="$apiKey ?? null" />
    <link rel="stylesheet" href="{{ asset('css/user.css') }}?v={{ filemtime(public_path('css/user.css')) }}">
</head>
<body class="dash-body dash-guest-body user-guest-body">
    <main class="dash-guest-main">
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
