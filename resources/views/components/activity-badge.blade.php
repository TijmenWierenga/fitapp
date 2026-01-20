@props(['sport'])

@php
    $sportEnum = is_string($sport) ? \App\Enums\Workout\Sport::from($sport) : $sport;
@endphp

<flux:badge
    color="{{ $sportEnum->color() }}"
    icon="{{ $sportEnum->icon() }}"
    size="sm"
>
    {{ $sportEnum->label() }}
</flux:badge>
