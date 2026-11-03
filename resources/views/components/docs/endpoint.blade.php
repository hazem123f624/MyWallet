@props(['item'])

@php
    $headers = $item->headers ? json_decode($item->headers, true) : [];
    $queryParams = $item->query_params ? json_decode($item->query_params, true) : [];
    $body = $item->body ? json_decode($item->body, true) : [];
    $notes = $item->notes ? json_decode($item->notes, true) : [];

    $hasRequestData = ! empty($headers) || ! empty($queryParams) || ! empty($body) || ! empty($notes);
    $hasResponses = ! empty($item->success_response) || ! empty($item->error_response);
    $searchText = strtolower(implode(' ', array_filter([
        $item->name,
        $item->method,
        $item->route,
        $item->short_description,
    ])));
@endphp

<article
    id="id-{{ $item->id }}"
    class="endpoint-card"
    data-search="{{ $searchText }}"
    data-method="{{ strtolower($item->method) }}"
>
    <header class="endpoint-header">
        <div class="endpoint-line">
            <span class="method method-{{ strtolower($item->method) }}">
                {{ strtoupper($item->method) }}
            </span>
            <code class="endpoint-path">{{ $item->route }}</code>
        </div>

        <h4 class="endpoint-title">{{ $item->name }}</h4>

        @if($item->short_description)
            <p class="endpoint-summary">{{ $item->short_description }}</p>
        @endif
    </header>

    @if($item->description)
        <div class="endpoint-description">
            {!! nl2br(e($item->description)) !!}
        </div>
    @endif

    <div class="endpoint-body">
        <div class="endpoint-request">
            @if($hasRequestData)
                <x-docs.param-table type="headers" title="Headers" :data="$item->headers" />
                <x-docs.param-table type="query" title="Query parameters" :data="$item->query_params" />
                <x-docs.param-table type="body" title="Request body" :data="$item->body" />
                <x-docs.param-table type="notes" title="Validation rules" :data="$item->notes" />
            @else
                <p class="empty-state">No request parameters.</p>
            @endif
        </div>

        <div class="endpoint-responses">
            @if($hasResponses)
                <x-docs.code-box
                    title="Success response"
                    :content="$item->success_response"
                />

                <x-docs.code-box
                    title="Error response"
                    :content="$item->error_response"
                />
            @else
                <p class="empty-state">No response example.</p>
            @endif
        </div>
    </div>
</article>
