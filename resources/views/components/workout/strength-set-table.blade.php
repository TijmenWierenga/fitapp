@use('App\Support\Workout\TimeConverter')
@use('App\Support\Workout\WorkoutDisplayFormatter')

@props(['exerciseSets'])

@php
    $hasDuration = $exerciseSets->contains(fn ($set) => $set->set_duration !== null);
@endphp

<flux:table>
    <flux:table.columns>
        <flux:table.column>Set</flux:table.column>
        <flux:table.column>Reps</flux:table.column>
        <flux:table.column>Weight</flux:table.column>
        @if($hasDuration)
            <flux:table.column>Duration</flux:table.column>
        @endif
    </flux:table.columns>

    <flux:table.rows>
        @foreach($exerciseSets as $set)
            <flux:table.row :key="$set->id">
                <flux:table.cell class="tabular-nums">{{ $set->set_number }}</flux:table.cell>
                <flux:table.cell class="tabular-nums">{{ $set->reps ?? '-' }}</flux:table.cell>
                <flux:table.cell class="tabular-nums">{{ WorkoutDisplayFormatter::weight($set->weight) ?? '-' }}</flux:table.cell>
                @if($hasDuration)
                    <flux:table.cell class="tabular-nums">{{ $set->set_duration ? TimeConverter::format($set->set_duration) : '-' }}</flux:table.cell>
                @endif
            </flux:table.row>
        @endforeach
    </flux:table.rows>
</flux:table>
