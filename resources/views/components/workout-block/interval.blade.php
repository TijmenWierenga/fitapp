@use('App\Support\Workout\TimeConverter')
@use('App\Support\Workout\DistanceConverter')
@use('App\Support\Workout\PaceConverter')

@props(['interval'])

@php
    $intensityColors = [
        'easy' => 'green',
        'moderate' => 'yellow',
        'threshold' => 'orange',
        'tempo' => 'red',
        'vo2max' => 'rose',
        'sprint' => 'fuchsia',
    ];
@endphp

<div class="flex flex-wrap items-center gap-2 pl-6 pb-2 text-sm text-zinc-600 dark:text-zinc-400">
    @if($interval->intensity)
        <flux:badge size="sm" :color="$intensityColors[$interval->intensity->value] ?? 'zinc'">
            {{ $interval->intensity->label() }}
        </flux:badge>
    @endif

    @if($interval->duration_seconds)
        <span>{{ TimeConverter::format($interval->duration_seconds) }}</span>
    @endif

    @if($interval->distance_meters)
        <span>{{ DistanceConverter::format($interval->distance_meters) }}</span>
    @endif

    @if($interval->target_pace_seconds_per_km)
        <span class="text-zinc-500 dark:text-zinc-500">{{ PaceConverter::format($interval->target_pace_seconds_per_km) }}</span>
    @endif

    @if($interval->target_heart_rate_zone)
        <flux:badge size="sm" color="red">Zone {{ $interval->target_heart_rate_zone }}</flux:badge>
    @endif
</div>
