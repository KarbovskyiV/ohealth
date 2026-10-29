@props(['label', 'value' => null, 'date' => false])
<div {{ $attributes->class(['min-w-0']) }}>
    <div class="record-inner-label">{{ $label }}</div>
    <div class="input flex min-h-10 items-center gap-2 break-words">
        @if ($date) @icon('calendar', 'w-4 h-4 shrink-0 text-gray-500') @endif
        @if ($slot->isNotEmpty())
            {{ $slot }}
        @else
            {{ filled($value) ? ($date ? convertToAppDateFormat($value) : $value) : '-' }}
        @endif
    </div>
</div>
