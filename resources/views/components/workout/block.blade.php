@use('App\Enums\Workout\BlockType')
@use('App\Support\Workout\WorkoutDisplayFormatter as Format')

@props(['block'])

@php
    $borderClass = match($block->block_type) {
        BlockType::StraightSets => 'border-zinc-400 dark:border-zinc-500',
        BlockType::Circuit => 'border-amber-400 dark:border-amber-500',
        BlockType::Superset => 'border-violet-400 dark:border-violet-500',
        BlockType::Interval => 'border-blue-400 dark:border-blue-500',
        BlockType::Amrap => 'border-red-400 dark:border-red-500',
        BlockType::ForTime => 'border-orange-400 dark:border-orange-500',
        BlockType::Emom => 'border-cyan-400 dark:border-cyan-500',
        BlockType::DistanceDuration => 'border-green-400 dark:border-green-500',
        BlockType::Rest => 'border-zinc-300 dark:border-zinc-600',
    };

    $isRest = $block->block_type === BlockType::Rest;
    $isDefault = $block->block_type === BlockType::StraightSets;
@endphp

<div class="border-l-2 pl-4 {{ $borderClass }} {{ $isRest ? 'border-dashed' : '' }}">
    {{-- Block header: type label for non-default types --}}
    @unless($isDefault)
        <flux:heading size="sm" class="text-{{ $block->block_type->color() }}-600 dark:text-{{ $block->block_type->color() }}-400">
            {{ $block->block_type->label() }}
        </flux:heading>
    @endunless

    {{-- Block details --}}
    <div class="{{ $isDefault ? '' : 'mt-2 ' }}space-y-1 text-sm text-zinc-500 dark:text-zinc-400">
        @if($block->rounds)
            <div class="flex items-center gap-2">
                <flux:icon.arrow-path class="size-4 shrink-0" />
                <span>{{ $block->rounds }} rounds</span>
            </div>
        @endif

        @if($intervals = Format::intervals($block->work_interval, $block->rest_interval))
            <div class="flex items-center gap-2">
                <flux:icon.clock class="size-4 shrink-0" />
                <span>{{ $intervals }}</span>
            </div>
        @endif

        @if($timeCap = Format::duration($block->time_cap))
            <div class="flex items-center gap-2">
                <flux:icon.clock class="size-4 shrink-0" />
                <span>Time cap: {{ $timeCap }}</span>
            </div>
        @endif

        @if($restBetweenRounds = Format::rest($block->rest_between_rounds))
            <div class="flex items-center gap-2">
                <flux:icon.pause class="size-4 shrink-0" />
                <span>{{ $restBetweenRounds }} rest between rounds</span>
            </div>
        @endif

        @if($restBetweenExercises = Format::rest($block->rest_between_exercises))
            <div class="flex items-center gap-2">
                <flux:icon.pause class="size-4 shrink-0" />
                <span>{{ $restBetweenExercises }} rest between exercises</span>
            </div>
        @endif

        @if($block->notes)
            <div class="flex items-center gap-2 italic">
                <flux:icon.chat-bubble-left class="size-4 shrink-0" />
                <span>{{ $block->notes }}</span>
            </div>
        @endif
    </div>

    {{-- Exercises --}}
    @unless($isRest)
        <div class="mt-3 space-y-2">
            @foreach($block->exercises as $exercise)
                <x-workout.exercise :exercise="$exercise" />
            @endforeach
        </div>
    @endunless
</div>
