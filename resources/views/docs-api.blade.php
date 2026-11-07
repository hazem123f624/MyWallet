<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Reference | {{ config('app.name') }}</title>

    <link rel="icon" type="image/svg+xml" href="{{ asset('images/wallet-icon.svg') }}">

    <script>
        (function () {
            var savedTheme = null;

            try {
                savedTheme = window.localStorage.getItem('mywallet-docs-theme');
            } catch (error) {
                savedTheme = null;
            }

            var theme = savedTheme || (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.dataset.theme = theme;
        })();
    </script>

    <link
        href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism.min.css"
        rel="stylesheet"
    />

    <link rel="stylesheet" href="{{ asset('css/docs.css') }}?v={{ filemtime(public_path('css/docs.css')) }}">
</head>
<body>

<div class="layout">
    <x-docs.sidebar :apiSections="$apiSections" />

    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <main class="main">
        <header class="topbar">
            <button
                class="icon-button menu-button"
                id="menuButton"
                type="button"
                aria-label="Open navigation"
                aria-controls="sidebar"
                aria-expanded="false"
            >
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>

            <div class="topbar-title">API Reference</div>

            <button
                class="icon-button theme-toggle"
                id="themeToggle"
                type="button"
                aria-label="Switch to night mode"
                aria-pressed="false"
            >
                <svg class="moon-icon" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z" />
                </svg>
                <svg class="sun-icon" viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="12" cy="12" r="4" />
                    <path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4" />
                </svg>
            </button>

            <div class="topbar-meta">
                <span>{{ $totalEndpoints }} endpoints</span>
                <span class="meta-divider"></span>
                <span>{{ config('api.version') }}</span>
            </div>
        </header>

        <div class="content" id="mainContent">
            <section id="introduction" class="hero">
                <span class="eyebrow">Getting started</span>
                <h1>{{ config('app.name') }} API</h1>

                <p class="hero-lead">
                    MyWallet is a personal money-management platform. A user links a bank account,
                    receives a Parent Wallet, and divides that balance across up to 12 Sub Wallets.
                </p>

                <div class="hero-grid">
                    <div class="info-card">
                        <h2>Base URL</h2>
                        <code class="inline-code">{{ $baseUrl }}</code>
                        <p>Every endpoint below is relative to this URL.</p>
                    </div>

                    <div class="info-card">
                        <h2>Authentication</h2>
                        <p>Send the API key with every request:</p>
                        <code class="inline-code">X-API-Token: your-api-key</code>
                        <p>Protected routes also need a bearer token:</p>
                        <code class="inline-code">Authorization: Bearer your-token</code>
                    </div>
                </div>

                <div class="note">
                    <strong>Two API surfaces.</strong>
                    The <b>User App</b> handles mobile account and wallet actions. The
                    <b>Dashboard</b> handles administration and user management.
                </div>
            </section>

            <p class="empty-state search-empty" id="searchEmpty" hidden>
                No endpoints match the current filters.
            </p>

            <section id="common-errors" class="section">
                <div class="section-heading">
                    <div>
                        <span class="eyebrow">Reference</span>
                        <h2>Common errors</h2>
                    </div>
                    <p>Every endpoint uses the same error response shape.</p>
                </div>

                <div class="error-list">
                    @forelse($commonErrors as $error)
                        <article class="error-card">
                            <div>
                                <h3>{{ $error->name }}</h3>
                                <p>{{ $error->short_description }}</p>
                            </div>

                            <x-docs.code-box
                                title="Example response"
                                :content="$error->error_response"
                            />
                        </article>
                    @empty
                        <p class="empty-state">No common errors have been documented yet.</p>
                    @endforelse
                </div>
            </section>

            @foreach($apiSections as $sectionName => $modules)
                <section
                    id="consumer-{{ \Illuminate\Support\Str::slug($sectionName) }}"
                    class="section"
                >
                    <div class="section-heading">
                        <div>
                            <span class="eyebrow">API surface</span>
                            <h2 class="consumer-title">{{ $sectionName }}</h2>
                        </div>
                        <p>{{ collect($modules)->sum(fn ($module) => $module['total']) }} endpoints</p>
                    </div>

                    @foreach($modules as $moduleName => $module)
                        <div class="module-block">
                            <div class="module-heading">
                                <h3 id="module-{{ \Illuminate\Support\Str::slug($sectionName.' '.$moduleName) }}">
                                    {{ $moduleName }}
                                </h3>
                                <span class="count-badge">{{ $module['total'] }}</span>
                            </div>

                            @foreach($module['groups'] as $group)
                                @if($group['name'])
                                    <div class="endpoint-group">
                                        <div class="group-heading">
                                            <h4 id="group-{{ \Illuminate\Support\Str::slug($sectionName.' '.$moduleName.' '.$group['name']) }}">
                                                {{ $group['name'] }}
                                            </h4>
                                            <span class="count-badge">{{ count($group['items']) }}</span>
                                        </div>

                                        <div class="endpoint-list">
                                            @foreach($group['items'] as $item)
                                                <x-docs.endpoint :item="$item" :group="$group['name']" :module="$moduleName" :section="$sectionName" />
                                            @endforeach
                                        </div>
                                    </div>
                                @else
                                    <div class="endpoint-list">
                                        @foreach($group['items'] as $item)
                                            <x-docs.endpoint :item="$item" :group="$group['name']" :module="$moduleName" :section="$sectionName" />
                                        @endforeach
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @endforeach
                </section>
            @endforeach

            <footer class="footer">
                <p>{{ config('app.name') }} API reference. Generated from the live documentation data.</p>
            </footer>
        </div>
    </main>
</div>

<button class="back-to-top" id="backToTop" type="button" aria-label="Back to top">
    <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M12 19V5M5 12l7-7 7 7" />
    </svg>
</button>

<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-json.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-bash.min.js"></script>
<script src="{{ asset('js/docs.js') }}?v={{ filemtime(public_path('js/docs.js')) }}"></script>
</body>
</html>
