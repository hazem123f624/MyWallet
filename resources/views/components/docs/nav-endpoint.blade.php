@props(['item', 'group' => null, 'module' => null, 'section' => null])

@php
    $searchText = strtolower(trim(implode(' ', array_filter([
        $section,
        $module,
        $group,
        $item->name,
        $item->method,
        $item->route,
    ]))));
@endphp

<a
    href="#id-{{ $item->id }}"
    class="nav-link nav-endpoint"
    data-search="{{ $searchText }}"
    data-method="{{ strtolower($item->method) }}"
>
    <span class="nav-method nav-method-{{ strtolower($item->method) }}">
        {{ strtoupper($item->method) }}
    </span>
    <span>{{ $item->name }}</span>
</a>
