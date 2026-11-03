@props(['title', 'content', 'language' => 'json'])

@if($content)
    <div class="code-box">
        <div class="code-header">
            <span>{{ $title }}</span>
{{--            <button class="copy-btn" onclick="copyCode(this)">Copy</button>--}}
        </div>
        <div class="code-content">
            <pre><code class="language-{{ $language }}">{!! $language === 'json' ? json_encode(json_decode($content), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : $content !!}</code></pre>
        </div>
    </div>
@endif
