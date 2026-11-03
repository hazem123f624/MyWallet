@props(['apiSections'])
<aside class="sidebar">
    <div class="logo-area">
{{--        <div class="logo-icon">--}}
{{--            --}}
{{--        </div>--}}
        <h1>{{  config('app.name') }}</h1>
    </div>

{{--    <div class="search-box">--}}
{{--        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">--}}
{{--            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />--}}
{{--        </svg>--}}
{{--        <input type="text" id="apiSearch" placeholder="Search endpoints...">--}}
{{--    </div>--}}

    <div class="nav-section dropdown-section active">
        <button class="nav-title dropdown-toggle" data-target="fundamentals-menu">
            Fundamentals
            <svg class="chevron-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
        </button>
        <div class="dropdown-content show" id="fundamentals-menu">
            <a href="#introduction" class="nav-item">Introduction</a>
            <a href="#common-errors" class="nav-item">Common Errors</a>
        </div>
    </div>

    {{--
        Navigation is derived automatically from the endpoint URLs at render time
        (DocumentationService::homePage). Each consumer section is a header and
        each module a collapsible group — no manual section_categories map.
    --}}
    <div class="nav-section">
        @foreach($apiSections as $sectionName => $modules)
            <button class="nav-group-title" style="" disabled>
                {{ $sectionName }}
            </button>

            @foreach($modules as $moduleName => $items)
                @php
                    $menuId = \Illuminate\Support\Str::slug($sectionName.' '.$moduleName).'-menu';
                @endphp
                <button class="nav-title dropdown-toggle" style="" data-target="{{ $menuId }}">
                    {{ $moduleName }}
                    <svg class="chevron-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
                <div class="dropdown-content" id="{{ $menuId }}">
                    @foreach($items as $item)
                        <a href="#id-{{ $item->id }}" class="nav-item">{{ $item->name }}</a>
                    @endforeach
                </div>
            @endforeach
        @endforeach
    </div>
</aside>
