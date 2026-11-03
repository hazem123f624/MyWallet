@props(['type','title', 'data'])

@if($data)
    <h4 style="font-size: 0.9rem; text-transform: uppercase; color: var(--text-dim); margin-top: 2rem; margin-bottom: 1rem;">
        {{ $title }}
    </h4>
    <div class="table-container">
        <table>
            <thead>

                <tr>
                    <th>Field/Name</th>
                    @if($type != 'notes')
                        <th>Type</th>
                    @endif
                    <th>Examples</th>
                </tr>
            </thead>
            <tbody>

                @foreach(json_decode($data, true) ?: [] as $key => $val)
                    <tr>
                        <td><span class="param-name">
                                 @if($type == 'notes')
                                    {{ $val['key'] }}
                                @else
                                    {{ $key }}
                                @endif

                            </span></td>
                        @if($type != 'notes')
                            <td><span class="type-label">{{ gettype($val) }}</span></td>
                        @endif

{{--                        //$title == 'Headers' &&--}}
                    @if(in_array($key,['Authorization','X-API-Token','X-SIGNATURE' , 'X-TIMESTAMP','token']))
                            <td style="color: var(--text-dim);">
                                {{ $key }} Key
                            </td>
                        @else
                            <td style="color: var(--text-dim);">
                                @if($type == 'notes')
                                    {{ $val['value'] }}
                                @else
                                    {{ is_array($val) ? json_encode($val) : $val }}
                                @endif

                            </td>
                        @endif

                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
