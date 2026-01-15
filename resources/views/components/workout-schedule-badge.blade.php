@props(['scheduledAt'])

@php
    $color = match(true) {
        $scheduledAt->isToday() => 'green',
        $scheduledAt->isTomorrow() => 'blue',
        default => 'zinc',
    };
@endphp

<flux:badge :color="$color" size="sm">
    @if($scheduledAt->isToday())
        Today
    @elseif($scheduledAt->isTomorrow())
        Tomorrow
    @else
        {{ $scheduledAt->diffForHumans() }}
    @endif
</flux:badge>
