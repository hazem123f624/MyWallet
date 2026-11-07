@props(['title', 'description' => null])

<div class="dash-page-header">
    <div>
        <h2>{{ $title }}</h2>

        @if($description)
            <p>{{ $description }}</p>
        @endif
    </div>

    @if(isset($actions))
        <div class="dash-page-actions">
            {{ $actions }}
        </div>
    @endif
</div>
