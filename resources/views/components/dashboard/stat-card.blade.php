@props(['label', 'valueId', 'tone' => 'brand', 'hint' => null, 'icon' => null])

<article class="dash-stat-card dash-stat-{{ $tone }}">
    <div class="dash-stat-head">
        <span class="dash-stat-label">{{ $label }}</span>

        @if($icon)
            <span class="dash-stat-icon">
                <x-dashboard.icon :name="$icon" />
            </span>
        @endif
    </div>

    <strong class="dash-stat-value" id="{{ $valueId }}">—</strong>

    @if($hint)
        <span class="dash-stat-hint">{{ $hint }}</span>
    @endif
</article>
