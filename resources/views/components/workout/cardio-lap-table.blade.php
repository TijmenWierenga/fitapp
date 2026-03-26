@use('App\Support\Workout\DistanceConverter')
@use('App\Support\Workout\PaceConverter')
@use('App\Support\Workout\TimeConverter')

@props(['exerciseSets'])

@php
    $hasDistance = $exerciseSets->contains(fn ($set) => $set->distance !== null);
    $hasDuration = $exerciseSets->contains(fn ($set) => $set->duration !== null);
    $hasPace = $exerciseSets->contains(fn ($set) => $set->avg_pace !== null);
    $hasAvgHr = $exerciseSets->contains(fn ($set) => $set->avg_heart_rate !== null);
    $hasMaxHr = $exerciseSets->contains(fn ($set) => $set->max_heart_rate !== null);
    $hasPower = $exerciseSets->contains(fn ($set) => $set->avg_power !== null);
    $hasCadence = $exerciseSets->contains(fn ($set) => $set->avg_cadence !== null);
    $hasAscent = $exerciseSets->contains(fn ($set) => $set->total_ascent !== null);

    $totalDistance = $hasDistance ? (int) $exerciseSets->sum('distance') : null;
    $totalDuration = $hasDuration ? (int) $exerciseSets->sum('duration') : null;
    $avgPace = ($hasPace && $totalDistance && $totalDuration)
        ? (int) round($totalDuration / ($totalDistance / 1000))
        : null;
    $avgHr = $hasAvgHr
        ? (int) round($exerciseSets->avg('avg_heart_rate'))
        : null;
    $avgPower = $hasPower
        ? (int) round($exerciseSets->avg('avg_power'))
        : null;
    $avgCadence = $hasCadence
        ? (int) round($exerciseSets->avg('avg_cadence'))
        : null;
    $totalAscent = $hasAscent ? (int) $exerciseSets->sum('total_ascent') : null;
@endphp

<flux:table>
    <flux:table.columns>
        <flux:table.column>Lap</flux:table.column>
        @if($hasDistance)
            <flux:table.column>Distance</flux:table.column>
        @endif
        @if($hasDuration)
            <flux:table.column>Time</flux:table.column>
        @endif
        @if($hasPace)
            <flux:table.column>Pace</flux:table.column>
        @endif
        @if($hasAvgHr)
            <flux:table.column>Avg HR</flux:table.column>
        @endif
        @if($hasMaxHr)
            <flux:table.column>Max HR</flux:table.column>
        @endif
        @if($hasPower)
            <flux:table.column>Power</flux:table.column>
        @endif
        @if($hasCadence)
            <flux:table.column>Cadence</flux:table.column>
        @endif
        @if($hasAscent)
            <flux:table.column>Ascent</flux:table.column>
        @endif
    </flux:table.columns>

    <flux:table.rows>
        @foreach($exerciseSets as $set)
            <flux:table.row :key="$set->id">
                <flux:table.cell class="tabular-nums">{{ $set->set_number }}</flux:table.cell>
                @if($hasDistance)
                    <flux:table.cell class="tabular-nums">{{ $set->distance !== null ? DistanceConverter::format((int) $set->distance) : '-' }}</flux:table.cell>
                @endif
                @if($hasDuration)
                    <flux:table.cell class="tabular-nums">{{ $set->duration !== null ? TimeConverter::format($set->duration) : '-' }}</flux:table.cell>
                @endif
                @if($hasPace)
                    <flux:table.cell class="tabular-nums">{{ $set->avg_pace !== null ? PaceConverter::format($set->avg_pace) : '-' }}</flux:table.cell>
                @endif
                @if($hasAvgHr)
                    <flux:table.cell class="tabular-nums">{{ $set->avg_heart_rate ?? '-' }}</flux:table.cell>
                @endif
                @if($hasMaxHr)
                    <flux:table.cell class="tabular-nums">{{ $set->max_heart_rate ?? '-' }}</flux:table.cell>
                @endif
                @if($hasPower)
                    <flux:table.cell class="tabular-nums">{{ $set->avg_power !== null ? $set->avg_power . ' W' : '-' }}</flux:table.cell>
                @endif
                @if($hasCadence)
                    <flux:table.cell class="tabular-nums">{{ $set->avg_cadence ?? '-' }}</flux:table.cell>
                @endif
                @if($hasAscent)
                    <flux:table.cell class="tabular-nums">{{ $set->total_ascent !== null ? $set->total_ascent . ' m' : '-' }}</flux:table.cell>
                @endif
            </flux:table.row>
        @endforeach

        {{-- Totals/averages row --}}
        <flux:table.row>
            <flux:table.cell class="font-semibold">Total</flux:table.cell>
            @if($hasDistance)
                <flux:table.cell class="tabular-nums font-semibold">{{ $totalDistance !== null ? DistanceConverter::format($totalDistance) : '-' }}</flux:table.cell>
            @endif
            @if($hasDuration)
                <flux:table.cell class="tabular-nums font-semibold">{{ $totalDuration !== null ? TimeConverter::format($totalDuration) : '-' }}</flux:table.cell>
            @endif
            @if($hasPace)
                <flux:table.cell class="tabular-nums font-semibold">{{ $avgPace !== null ? PaceConverter::format($avgPace) : '-' }}</flux:table.cell>
            @endif
            @if($hasAvgHr)
                <flux:table.cell class="tabular-nums font-semibold">{{ $avgHr ?? '-' }}</flux:table.cell>
            @endif
            @if($hasMaxHr)
                <flux:table.cell class="tabular-nums font-semibold">-</flux:table.cell>
            @endif
            @if($hasPower)
                <flux:table.cell class="tabular-nums font-semibold">{{ $avgPower !== null ? $avgPower . ' W' : '-' }}</flux:table.cell>
            @endif
            @if($hasCadence)
                <flux:table.cell class="tabular-nums font-semibold">{{ $avgCadence ?? '-' }}</flux:table.cell>
            @endif
            @if($hasAscent)
                <flux:table.cell class="tabular-nums font-semibold">{{ $totalAscent !== null ? $totalAscent . ' m' : '-' }}</flux:table.cell>
            @endif
        </flux:table.row>
    </flux:table.rows>
</flux:table>
