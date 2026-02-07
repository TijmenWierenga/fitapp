@use('App\Enums\Workout\BlockType')
@use('App\Support\Workout\TimeConverter')

@props(['block', 'depth' => 1, 'compact' => false])

@php
    $borderColors = [
        BlockType::Group->value => 'border-zinc-300 dark:border-zinc-600',
        BlockType::Interval->value => 'border-amber-400 dark:border-amber-500',
        BlockType::ExerciseGroup->value => 'border-blue-400 dark:border-blue-500',
        BlockType::Rest->value => 'border-emerald-400 dark:border-emerald-500',
        BlockType::Note->value => 'border-purple-400 dark:border-purple-500',
    ];

    $icons = [
        BlockType::Group->value => 'folder',
        BlockType::Interval->value => 'bolt',
        BlockType::ExerciseGroup->value => 'list-bullet',
        BlockType::Rest->value => 'pause-circle',
        BlockType::Note->value => 'document-text',
    ];

    $iconColors = [
        BlockType::Group->value => 'text-zinc-400 dark:text-zinc-500',
        BlockType::Interval->value => 'text-amber-500 dark:text-amber-400',
        BlockType::ExerciseGroup->value => 'text-blue-500 dark:text-blue-400',
        BlockType::Rest->value => 'text-emerald-500 dark:text-emerald-400',
        BlockType::Note->value => 'text-purple-500 dark:text-purple-400',
    ];

    $borderColor = $borderColors[$block->type->value] ?? 'border-zinc-300 dark:border-zinc-600';
    $icon = $icons[$block->type->value] ?? 'document';
    $iconColor = $iconColors[$block->type->value] ?? 'text-zinc-400 dark:text-zinc-500';
@endphp

<div @class([
    'border-l-2 ml-2 pl-4' => $depth > 1,
    $borderColor => $depth > 1,
])>
    {{-- Block header --}}
    <div class="flex items-center gap-2 py-1.5">
        <flux:icon :name="$icon" :class="'size-4 flex-shrink-0 ' . $iconColor" />

        @if($block->label)
            <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $block->label }}</span>
        @endif

        @if($block->repeat_count > 1)
            <flux:badge size="sm" color="zinc">{{ $block->repeat_count }}x</flux:badge>
        @endif

        @if($block->repeat_count > 1 && $block->rest_between_repeats_seconds)
            <span class="text-xs text-zinc-500 dark:text-zinc-400">
                ({{ TimeConverter::format($block->rest_between_repeats_seconds) }} rest between)
            </span>
        @endif
    </div>

    {{-- Block content --}}
    @if(! $compact)
        @switch($block->type)
            @case(BlockType::Interval)
                @if($block->blockable)
                    <x-workout-block.interval :interval="$block->blockable" />
                @endif
                @break
            @case(BlockType::ExerciseGroup)
                @if($block->blockable)
                    <x-workout-block.exercise-group :group="$block->blockable" />
                @endif
                @break
            @case(BlockType::Rest)
                @if($block->blockable)
                    <x-workout-block.rest :rest="$block->blockable" />
                @endif
                @break
            @case(BlockType::Note)
                @if($block->blockable)
                    <x-workout-block.note :note="$block->blockable" />
                @endif
                @break
        @endswitch
    @endif

    {{-- Nested children --}}
    @if($block->nestedChildren->isNotEmpty())
        <div class="space-y-4 mt-1">
            @foreach($block->nestedChildren as $child)
                <x-workout-block :block="$child" :depth="$depth + 1" :compact="$compact" />
            @endforeach
        </div>
    @endif
</div>
