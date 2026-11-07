@props(['id' => null, 'title', 'message'])

<div class="dash-empty" @if($id) id="{{ $id }}" @endif hidden>
    <span class="dash-empty-mark">∅</span>
    <h3>{{ $title }}</h3>
    <p>{{ $message }}</p>
</div>
