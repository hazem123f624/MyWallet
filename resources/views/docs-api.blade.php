<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Documentation | MyWallet</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Fira+Code:wght@400;500&display=swap" rel="stylesheet">

    <!-- Prism.js for Beautiful Syntax Highlighting -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css" rel="stylesheet" />

    <!-- External CSS -->
    <link rel="stylesheet" href="{{ asset('css/docs.css') }}">
</head>
<body>

<x-docs.sidebar :apiSections="$apiSections" />

<main class="main-container">
    <div class="content-scroll" id="mainContent">
        <!-- Introduction Section -->
        <section id="introduction" class="section-wrapper">
            <div class="info-col">
                <h2>Introduction</h2>
                <p>
                    MyWallet is a personal money-management platform. A user links a bank account, receives a Parent Wallet, and divides that balance across up to 12 Sub Wallets used for different purposes.
                </p>
                <br>
                <p>
                    The API is split into two surfaces: the <strong>User App</strong> for mobile account and wallet actions, and the <strong>Dashboard</strong> for administration and user management.
                </p>
                <br>
                <p>
                    External transfers leave a Sub Wallet and are executed through a banking provider, moving through a clear reserve, confirm, and settlement lifecycle so no money is lost to failed transfers or duplicate requests.
                </p>
                <br>
                <br>
                <h3>Access Requirements</h3>
                <br>
                <p>
                    Before using this API, you must obtain your App Key, Secret Key, and the implementation details for X-SIGNATURE from the system administrator.
                </p>
                <p>
                    All API requests must include these credentials; any request without them will be rejected.
                </p>
            </div>
            <div class="code-col">
                <x-docs.code-box
                    title="Base API URL"
                    :content="config('app.url') . '/api/v1'"
                    language="bash"
                />
            </div>
        </section>
        <!-- Common Errors Section -->
        <section id="common-errors" class="section-wrapper">
            <div class="info-col">
                <h2>Common Errors</h2>
                <p class="short-desc">A guide to consistent error response formats used across all endpoints.</p>

                @foreach($commonErrors as $section)
                    <div style="margin-bottom: 3rem; padding-left: 1.5rem; border-left: 2px solid var(--accent-secondary);">
                        <h4 style="color: #fff; margin-bottom: 0.5rem;">{{ $section->name }}</h4>
                        <p style="color: var(--text-dim);">{{ $section->short_description }}</p>
                    </div>
                    <x-docs.code-box
                        title="Error Response"
                        :content="$section->error_response"
                    />
                @endforeach
            </div>

        </section>

            <!-- API Endpoints — grouped by consumer section → module (routing-derived) -->
        @foreach($apiSections as $sectionName => $modules)
            <section id="consumer-{{ \Illuminate\Support\Str::slug($sectionName) }}" class="consumer-section">
                <h2 class="consumer-title">{{ $sectionName }}</h2>
                @foreach($modules as $moduleName => $items)
                    <h3 id="module-{{ \Illuminate\Support\Str::slug($sectionName.' '.$moduleName) }}" class="module-title">{{ $moduleName }}</h3>
                    @foreach($items as $item)
                        <x-docs.endpoint :item="$item" />
                    @endforeach
                @endforeach
            </section>
        @endforeach

    </div>
</main>

<!-- Scripts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-json.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-bash.min.js"></script>

<!-- External JS -->
<script src="{{ asset('js/docs.js') }}"></script>
</body>
</html>
