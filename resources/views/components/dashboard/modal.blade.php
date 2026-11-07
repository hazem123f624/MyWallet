@props(['id', 'title'])

<div class="dash-modal" id="{{ $id }}" hidden>
    <div class="dash-modal-backdrop" data-close-modal></div>

    <div class="dash-modal-panel" role="dialog" aria-modal="true" aria-labelledby="{{ $id }}-title" tabindex="-1">
        <header class="dash-modal-header">
            <h2 id="{{ $id }}-title">{{ $title }}</h2>

            <button class="dash-icon-button" type="button" data-close-modal aria-label="Close dialog">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M6 6l12 12M18 6L6 18" />
                </svg>
            </button>
        </header>

        <div class="dash-modal-body">
            {{ $slot }}
        </div>

        @if(isset($footer))
            <footer class="dash-modal-footer">
                {{ $footer }}
            </footer>
        @endif
    </div>
</div>
