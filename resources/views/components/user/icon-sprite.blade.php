@php
    $icons = [
        'bolt' => '<path d="M13 2L4 14h7l-1 8 9-12h-7z" />',
        'home' => '<path d="M3 11l9-8 9 8M5 10v10h14V10" />',
        'utensils' => '<path d="M7 3v8M4 3v5a3 3 0 0 0 6 0V3M7 11v10M17 3v18M17 3c-2 0-3 2-3 5s1 4 3 4" />',
        'shopping-bag' => '<path d="M6 8h12l1 13H5L6 8zM9 8V6a3 3 0 0 1 6 0v2" />',
        'car' => '<path d="M5 16l2-6h10l2 6M3 16h18v4H3zM7 20v2M17 20v2M7 16h.01M17 16h.01" />',
        'heart' => '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8l1.1 1.1L12 21l7.7-7.5 1.1-1.1a5.5 5.5 0 0 0 0-7.8z" />',
        'plane' => '<path d="M22 2L11 13M22 2l-7 20-4-9-9-4z" />',
        'gift' => '<rect x="3" y="8" width="18" height="4" rx="1" /><path d="M5 12v9h14v-9M12 8v13M12 8C9 8 7 6 7 4a2 2 0 0 1 4 0c0 2 1 4 1 4zM12 8c3 0 5-2 5-4a2 2 0 0 0-4 0c0 2-1 4-1 4z" />',
        'book' => '<path d="M4 4h6a3 3 0 0 1 3 3v13a2 2 0 0 0-2-2H4zM20 4h-6a3 3 0 0 0-3 3v13a2 2 0 0 1 2-2h7z" />',
        'briefcase' => '<rect x="3" y="7" width="18" height="13" rx="2" /><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M3 12h18" />',
        'piggy-bank' => '<path d="M5 11a5 5 0 0 1 5-4h5l3-2v4a5 5 0 0 1 1 3v3a4 4 0 0 1-4 4H9a4 4 0 0 1-4-4zM9 11h.01M17 13h.01M6 9L4 8M10 19v2M15 19v2" />',
        'wallet' => '<path d="M4 6h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z" /><path d="M2 8h20M16 14h2" />',
    ];
@endphp

<svg xmlns="http://www.w3.org/2000/svg" style="position:absolute;width:0;height:0;overflow:hidden" aria-hidden="true">
    @foreach($icons as $name => $paths)
        <symbol id="user-icon-{{ $name }}" viewBox="0 0 24 24">
            {!! $paths !!}
        </symbol>
    @endforeach
</svg>
