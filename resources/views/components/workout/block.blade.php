@use('App\Enums\Workout\BlockType')
@use('App\Support\Workout\WorkoutDisplayFormatter as Format')

@props(['block'])

@php
    $isRest = $block->block_type === BlockType::Rest;
    $isSuperset = $block->block_type === BlockType::Superset;

    $iconColorClass = match($block->block_type) {
        BlockType::Circuit => 'text-amber-500',
        BlockType::Superset => 'text-violet-500',
        BlockType::Interval => 'text-blue-500',
        BlockType::Amrap => 'text-red-500',
        BlockType::ForTime => 'text-orange-500',
        BlockType::Emom => 'text-cyan-500',
        BlockType::DistanceDuration => 'text-green-500',
        default => 'text-zinc-500',
    };

    $metaItems = [];
    if ($block->rounds) {
        $metaItems[] = "{$block->rounds} rounds";
    }
    if ($intervals = Format::intervals($block->work_interval, $block->rest_interval)) {
        $metaItems[] = $intervals;
    }
    if ($timeCap = Format::duration($block->time_cap)) {
        $metaItems[] = "Time cap: {$timeCap}";
    }
    if ($restBetweenRounds = Format::rest($block->rest_between_rounds)) {
        $metaItems[] = "{$restBetweenRounds} between rounds";
    }
    if ($restBetweenExercises = Format::rest($block->rest_between_exercises)) {
        $metaItems[] = "{$restBetweenExercises} between exercises";
    }
@endphp

<div class="space-y-2">
    {{-- Rest block - simplified display --}}
    @if($isRest)
        <div class="rounded bg-zinc-100 dark:bg-zinc-950 px-3 py-2 flex items-center gap-2 text-xs text-zinc-500">
            <flux:icon.pause class="size-3.5" />
            <span>Rest</span>
            @if($restDuration = Format::duration($block->work_interval))
                <span>{{ $restDuration }}</span>
            @endif
        </div>
    @else
        {{-- Block header --}}
        <div class="flex items-center gap-2 text-xs text-zinc-600 dark:text-zinc-400">
            <flux:icon icon="{{ $block->block_type->icon() }}" class="size-3.5 {{ $iconColorClass }}" />
            <span class="font-medium {{ $iconColorClass }}">{{ $block->block_type->label() }}</span>
            @if(!empty($metaItems))
                <span class="text-zinc-500">·</span>
                <span>{{ implode(' · ', $metaItems) }}</span>
            @endif
        </div>

        {{-- Exercises --}}
        @if($isSuperset)
            <div class="rounded border border-accent/20 dark:border-accent/20 overflow-hidden">
                @foreach($block->exercises as $exercise)
                    @if(!$loop->first)
                        <div class="border-t border-zinc-200 dark:border-zinc-700"></div>
                    @endif
                    <x-workout.exercise :exercise="$exercise" />
                @endforeach
            </div>
        @else
            <div class="space-y-1.5">
                @foreach($block->exercises as $exercise)
                    <x-workout.exercise :exercise="$exercise" />
                @endforeach
            </div>
        @endif

        {{-- Block note --}}
        @if($block->notes)
            <div class="rounded bg-accent/5 dark:bg-accent/10 p-2.5 flex items-start gap-2">
                <flux:icon.chat-bubble-left class="size-3.5 text-accent shrink-0 mt-0.5" />
                <div class="text-xs text-zinc-600 dark:text-zinc-400">{{ $block->notes }}</div>
            </div>
        @endif
    @endif
</div>
