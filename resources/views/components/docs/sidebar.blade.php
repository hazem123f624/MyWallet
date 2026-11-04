@props(['apiSections'])

@php
    $methodOrder = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];
    $methods = collect($apiSections)
        ->flatMap(fn ($modules) => collect($modules)->flatMap(
            fn ($module) => collect($module['groups'])->flatMap(fn ($group) => $group['items'])
        ))
        ->pluck('method')
        ->map(fn ($method) => strtoupper($method))
        ->unique()
        ->sortBy(fn ($method) => array_search($method, $methodOrder, true) !== false ? array_search($method, $methodOrder, true) : 99)
        ->values();
@endphp

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="#introduction" class="brand">
            <span class="brand-mark">M</span>
            <span>
                <strong>{{ config('app.name') }}</strong>
                <small>API documentation</small>
            </span>
        </a>

        <button
            class="icon-button sidebar-close"
            id="sidebarClose"
            type="button"
            aria-label="Close navigation"
        >
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M6 6l12 12M18 6L6 18" />
            </svg>
        </button>
    </div>

    <div class="search-box">
        <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z" />
        </svg>
        <input
            type="search"
            id="apiSearch"
            placeholder="Search endpoints..."
            autocomplete="off"
            aria-label="Search endpoints"
        >
    </div>

    <div class="method-filters" role="group" aria-label="Filter by method">
        <button class="method-filter active" type="button" data-method-filter="all" aria-pressed="true">All</button>

        @foreach($methods as $method)
            <button
                class="method-filter"
                type="button"
                data-method-filter="{{ strtolower($method) }}"
                aria-pressed="false"
            >
                {{ $method }}
            </button>
        @endforeach
    </div>

    <nav class="sidebar-nav" aria-label="API navigation">
        <div class="nav-group">
            <p class="nav-label">Getting started</p>
            <a href="#introduction" class="nav-link" data-search="introduction getting started">
                Introduction
            </a>
            <a href="#common-errors" class="nav-link" data-search="common errors responses">
                Common errors
            </a>
        </div>

        @foreach($apiSections as $sectionName => $modules)
            <details class="nav-section" open>
                <summary>
                    <span>{{ $sectionName }}</span>
                    <span class="nav-count">{{ collect($modules)->sum('total') }}</span>
                </summary>

                <div class="nav-section-body">
                @foreach($modules as $moduleName => $module)
                    <details class="nav-module" open>
                        <summary>
                            <span>{{ $moduleName }}</span>
                            <span class="nav-count">{{ $module['total'] }}</span>
                        </summary>

                        <div class="nav-links">
                            @foreach($module['groups'] as $group)
                                @if($group['name'])
                                    <details class="nav-submodule">
                                        <summary>
                                            <span>{{ $group['name'] }}</span>
                                            <span class="nav-count">{{ count($group['items']) }}</span>
                                        </summary>

                                        <div class="nav-links nav-sub-links">
                                            @foreach($group['items'] as $item)
                                                <x-docs.nav-endpoint :item="$item" :group="$group['name']" />
                                            @endforeach
                                        </div>
                                    </details>
                                @else
                                    <div class="nav-links nav-sub-links">
                                        @foreach($group['items'] as $item)
                                            <x-docs.nav-endpoint :item="$item" :group="$group['name']" />
                                        @endforeach
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </details>
                @endforeach
                </div>
            </details>
        @endforeach
    </nav>

    <p class="sidebar-empty" id="sidebarEmpty" hidden>No matching endpoints.</p>

    <div
        class="sidebar-resizer"
        id="sidebarResizer"
        role="separator"
        aria-orientation="vertical"
        aria-label="Resize navigation"
        aria-valuemin="220"
        aria-valuemax="560"
        tabindex="0"
        title="Drag to resize"
    ></div>
</aside>
