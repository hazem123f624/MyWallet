@props(['type', 'title', 'data'])

@php
    $rows = json_decode($data, true) ?: [];

    $valueType = static function ($value): string {
        return match (true) {
            is_bool($value) => 'boolean',
            is_int($value), is_float($value) => 'number',
            is_array($value) => 'array',
            is_null($value) => 'null',
            default => 'string',
        };
    };

    $displayValue = static function ($value): string {
        return match (true) {
            is_array($value) => (string) json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            is_bool($value) => $value ? 'true' : 'false',
            is_null($value) => 'null',
            default => (string) $value,
        };
    };
@endphp

@if(! empty($rows))
    <div class="param-block">
        <h5 class="param-title">{{ $title }}</h5>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Field</th>
                        @if($type !== 'notes')
                            <th>Type</th>
                        @endif
                        <th>{{ $type === 'notes' ? 'Rules' : 'Example' }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $key => $value)
                        @php($name = (string) ($type === 'notes' ? ($value['key'] ?? $key) : $key))
                        <tr>
                            <td>
                                <code class="param-name">{!! str_replace('_', '_<wbr>', e($name)) !!}</code>
                            </td>

                            @if($type !== 'notes')
                                <td>
                                    <span class="type-label">{{ $valueType($value) }}</span>
                                </td>
                            @endif

                            <td class="param-example">
                                {{ $type === 'notes' ? ($value['value'] ?? '') : $displayValue($value) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
