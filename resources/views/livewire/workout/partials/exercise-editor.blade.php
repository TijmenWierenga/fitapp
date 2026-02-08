@props(['exercise', 'si', 'bi', 'ei'])

@php
    $prefix = "sections.{$si}.blocks.{$bi}.exercises.{$ei}";
@endphp

<div x-data="{ expanded: false }" class="rounded border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50">
    {{-- Exercise compact row --}}
    <div class="flex items-center gap-2 px-2 py-1.5">
        <div wire:sort:handle class="cursor-grab text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
            <flux:icon.bars-2 class="size-3" />
        </div>

        <button
            type="button"
            x-on:click="expanded = !expanded"
            class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300"
        >
            <flux:icon.chevron-right class="size-3 transition-transform" x-bind:class="expanded && 'rotate-90'" />
        </button>

        <flux:input
            wire:model="{{ $prefix }}.name"
            placeholder="Exercise name"
            size="xs"
            class="flex-1"
        />

        <flux:select
            wire:model.live="{{ $prefix }}.type"
            size="xs"
            class="w-28"
        >
            <option value="strength">Strength</option>
            <option value="cardio">Cardio</option>
            <option value="duration">Duration</option>
        </flux:select>

        {{-- Inline summary of key fields --}}
        @if($exercise['type'] === 'strength')
            @if($exercise['target_sets'] || $exercise['target_reps_max'])
                <flux:text class="text-xs text-zinc-400 whitespace-nowrap">
                    {{ $exercise['target_sets'] ?? '?' }}x{{ $exercise['target_reps_max'] ?? '?' }}
                    @if($exercise['target_weight']) @ {{ $exercise['target_weight'] }}kg @endif
                </flux:text>
            @endif
        @elseif($exercise['type'] === 'cardio')
            @if($exercise['target_duration'] || $exercise['target_distance'])
                <flux:text class="text-xs text-zinc-400 whitespace-nowrap">
                    @if($exercise['target_distance']) {{ $exercise['target_distance'] }}km @endif
                    @if($exercise['target_duration']) {{ gmdate('H:i:s', $exercise['target_duration']) }} @endif
                </flux:text>
            @endif
        @elseif($exercise['type'] === 'duration')
            @if($exercise['target_duration'])
                <flux:text class="text-xs text-zinc-400 whitespace-nowrap">
                    {{ gmdate('H:i:s', $exercise['target_duration']) }}
                </flux:text>
            @endif
        @endif

        <flux:button
            wire:click="removeExercise({{ $si }}, {{ $bi }}, {{ $ei }})"
            variant="ghost"
            size="xs"
            icon="x-mark"
            class="text-red-500 hover:text-red-700"
            title="Remove exercise"
        />
    </div>
    <flux:error name="{{ $prefix }}.name" class="px-2 pb-1" />

    {{-- Expanded fields --}}
    <div x-show="expanded" x-cloak class="px-2 pb-2 pt-1 border-t border-zinc-200 dark:border-zinc-700">
        @if($exercise['type'] === 'strength')
            @include('livewire.workout.partials.exercise-fields.strength-fields', ['prefix' => $prefix])
        @elseif($exercise['type'] === 'cardio')
            @include('livewire.workout.partials.exercise-fields.cardio-fields', ['prefix' => $prefix])
        @elseif($exercise['type'] === 'duration')
            @include('livewire.workout.partials.exercise-fields.duration-fields', ['prefix' => $prefix])
        @endif

        <flux:input
            wire:model="{{ $prefix }}.notes"
            placeholder="Exercise notes (optional)"
            size="xs"
            class="mt-2"
        />
    </div>
</div>
