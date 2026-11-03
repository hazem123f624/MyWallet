@props(['title', 'content', 'language' => 'json'])

@php
    $decoded = json_decode((string) $content, true);
    $isJson = json_last_error() === JSON_ERROR_NONE;
    $formatted = $isJson
        ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        : (string) $content;
@endphp

@if($content)
    <div class="code-box">
        <div class="code-header">
            <span>{{ $title }}</span>
            <button class="copy-button" type="button" data-copy>Copy</button>
        </div>

        <pre class="code-content"><code class="language-{{ $isJson ? 'json' : $language }}">{{ $formatted }}</code></pre>
    </div>
@endif
