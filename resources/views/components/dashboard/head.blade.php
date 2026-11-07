@props(['title', 'apiBaseUrl' => null, 'apiKey' => null, 'apiToken' => null])

<meta charset="UTF-8">
<script>
    (function () {
        try {
            var stored = localStorage.getItem('dashboard-theme');
            var theme = stored || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.dataset.theme = theme;
        } catch (error) {
            document.documentElement.dataset.theme = 'light';
        }
    })();
</script>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
@if($apiBaseUrl)
    <meta name="dashboard-api-base" content="{{ $apiBaseUrl }}">
@endif
@if($apiKey)
    <meta name="dashboard-api-key" content="{{ $apiKey }}">
@endif
@if($apiToken)
    <meta name="dashboard-api-token" content="{{ $apiToken }}">
@endif
<title>{{ $title }} | {{ config('app.name') }}</title>
<link rel="icon" type="image/svg+xml" href="{{ asset('images/wallet-icon.svg') }}">
<link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v={{ filemtime(public_path('css/dashboard.css')) }}">
@stack('head')
