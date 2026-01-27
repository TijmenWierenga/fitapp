@props(['activity'])

@php
    $activityEnum = is_string($activity) ? \App\Enums\Workout\Activity::from($activity) : $activity;
@endphp

<flux:badge
    color="{{ $activityEnum->color() }}"
    icon="{{ $activityEnum->icon() }}"
    size="sm"
>
    {{ $activityEnum->label() }}
</flux:badge>
