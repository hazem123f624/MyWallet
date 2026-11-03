@props(['apiSections'])

@php
    $methodOrder = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];
    $methods = collect($apiSections)
        ->flatMap(fn ($modules) => collect($modules)->flatMap(fn ($items) => $items))
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
            <div class="nav-group">
                <p class="nav-label">{{ $sectionName }}</p>

                @foreach($modules as $moduleName => $items)
                    <details class="nav-module" open>
                        <summary>
                            <span>{{ $moduleName }}</span>
                            <span class="nav-count">{{ count($items) }}</span>
                        </summary>

                        <div class="nav-links">
                            @foreach($items as $item)
                                <a
                                    href="#id-{{ $item->id }}"
                                     class="nav-link nav-endpoint"
                                     data-search="{{ strtolower($item->name.' '.$item->method.' '.$item->route) }}"
                                     data-method="{{ strtolower($item->method) }}"
                                 >
                                    <span class="nav-method nav-method-{{ strtolower($item->method) }}">
                                        {{ strtoupper($item->method) }}
                                    </span>
                                    <span>{{ $item->name }}</span>
                                </a>
                            @endforeach
                        </div>
                    </details>
                @endforeach
            </div>
        @endforeach
    </nav>

    <p class="sidebar-empty" id="sidebarEmpty" hidden>No matching endpoints.</p>
</aside>
