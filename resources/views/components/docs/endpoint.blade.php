@props(['item'])

<section id="id-{{ $item->id }}" class="section-wrapper">
    <div class="info-col">
        <div class="endpoint-pills">
            <span class="method-badge method-{{ strtoupper($item->method) }}">{{ $item->method }}</span>
            <span class="route-text">{{ $item->route }}</span>
        </div>
        <h2>{{ $item->name }}</h2>
        <p class="short-desc">{{ $item->short_description }}</p>

        @if($item->description)
            <div class="long-description">
                {!! nl2br(e($item->description)) !!}
            </div>
        @endif

        <x-docs.param-table
            type="headers"
            title="Headers"
            :data="$item->headers"
        />

        <x-docs.param-table
            type="query"
            title="Query Parameters"
            :data="$item->query_params"
        />

        <x-docs.param-table
            type="body"
            title="Request Body"
            :data="$item->body"
        />
        <x-docs.param-table
            type="notes"
            title="Request Validations Or Notes"
            :data="$item->notes"
        />

    </div>

    <div class="code-col">
        <x-docs.code-box
            title="Success (200 OK)"
            :content="$item->success_response"
        />

        <x-docs.code-box
            title="Error Response"
            :content="$item->error_response"
        />
    </div>
</section>
