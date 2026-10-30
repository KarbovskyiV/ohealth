@props(['value'])
@if (is_array($value))
    @if (isset($value['display_value']))
        {{ $value['display_value'] }}
    @elseif (isset($value['coding']))
        @if (filled($value['text'] ?? null))
            {{ $value['text'] }}
        @else
            @foreach ($value['coding'] as $coding)
                @php
                    $names = filled($coding['system'] ?? null) ? dictionary()->basics()->byName($coding['system'])->flattenedChildValues()->toArray() : [];
                @endphp
                <span>{{ $coding['display'] ?? $names[$coding['code'] ?? ''] ?? $coding['code'] ?? '-' }}</span>
            @endforeach
        @endif
    @elseif (isset($value['identifier']))
        {{ data_get($value, 'identifier.value', '-') }}
    @elseif (isset($value['value']))
        {{ $value['value'] }} {{ $value['unit'] ?? $value['code'] ?? '' }}
    @elseif (isset($value['text']))
        {{ $value['text'] }}
    @else
        <div class="space-y-1">
            @forelse ($value as $item)
                <div><x-device-request-value :value="$item" /></div>
            @empty
                -
            @endforelse
        </div>
    @endif
@elseif (is_bool($value))
    {{ $value ? __('device-requests.form.yes') : __('device-requests.form.no') }}
@else
    {{ $value ?? '-' }}
@endif
