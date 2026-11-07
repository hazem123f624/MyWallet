<!DOCTYPE html>
<html lang="en">
<head>
    <x-dashboard.head
        :title="$title"
        :api-base-url="$apiBaseUrl ?? null"
        :api-key="$apiKey ?? null"
    />
</head>
<body class="dash-body dash-guest-body">
    <main class="dash-guest-main">
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
