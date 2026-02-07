@use('App\Support\Workout\TimeConverter')

@props(['entry'])

<div class="py-1.5">
    <div class="text-xs font-medium text-zinc-700 dark:text-zinc-300">
        {{ $entry->exercise?->name ?? 'Unknown exercise' }}
    </div>

    <div class="flex flex-wrap gap-x-4 gap-y-0.5 mt-0.5 text-[11px] text-zinc-500 dark:text-zinc-400">
        @if($entry->sets && $entry->reps)
            <span>{{ $entry->sets }} &times; {{ $entry->reps }} reps</span>
        @elseif($entry->sets && $entry->duration_seconds)
            <span>{{ $entry->sets }} &times; {{ TimeConverter::format($entry->duration_seconds) }}</span>
        @elseif($entry->sets)
            <span>{{ $entry->sets }} sets</span>
        @endif

        @if($entry->weight_kg)
            <span>{{ $entry->weight_kg }} kg</span>
        @endif

        @if($entry->rpe_target)
            <span>RPE {{ $entry->rpe_target }}</span>
        @endif

        @if($entry->rest_between_sets_seconds)
            <span>{{ TimeConverter::format($entry->rest_between_sets_seconds) }} rest</span>
        @endif
    </div>

    @if($entry->notes)
        <div class="mt-0.5 text-[11px] text-zinc-400 dark:text-zinc-500 italic">
            {{ $entry->notes }}
        </div>
    @endif
</div>
