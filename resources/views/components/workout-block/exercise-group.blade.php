@use('App\Support\Workout\TimeConverter')

@props(['group'])

<div class="pl-6 pb-2 space-y-2">
    {{-- Group type and rounds --}}
    @if($group->rounds > 1)
        <div class="flex items-center gap-2 text-xs text-zinc-600 dark:text-zinc-400">
            <span>{{ $group->rounds }} rounds</span>

            @if($group->rest_between_rounds_seconds)
                <span class="text-xs text-zinc-500 dark:text-zinc-500">
                    ({{ TimeConverter::format($group->rest_between_rounds_seconds) }} rest between rounds)
                </span>
            @endif
        </div>
    @endif

    {{-- Exercise entries --}}
    @if($group->entries->isNotEmpty())
        <div class="divide-y divide-zinc-100 dark:divide-zinc-700/50">
            @foreach($group->entries as $entry)
                <x-workout-block.exercise-entry :entry="$entry" />
            @endforeach
        </div>
    @endif
</div>
