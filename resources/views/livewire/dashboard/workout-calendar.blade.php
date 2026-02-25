<div class="rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6">
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="lg">Workout Calendar</flux:heading>
        <div class="flex items-center gap-2">
            <flux:button wire:click="previousMonth" variant="ghost" size="sm" icon="chevron-left" aria-label="Previous month" />
            <flux:button wire:click="today" variant="ghost" size="sm" class="min-w-32">
                <span class="truncate">{{ $this->monthName }}</span>
            </flux:button>
            <flux:button wire:click="nextMonth" variant="ghost" size="sm" icon="chevron-right" aria-label="Next month" />
        </div>
    </div>

    <div class="mb-4">
        {{-- Day headers --}}
        <div class="grid grid-cols-7 gap-1 mb-2">
            @foreach(['S', 'M', 'T', 'W', 'T', 'F', 'S'] as $day)
                <div class="text-center text-[10px] font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-500">
                    {{ $day }}
                </div>
            @endforeach
        </div>

        {{-- Calendar grid --}}
        <div class="grid grid-cols-7 gap-1">
            @foreach($this->calendarWeeks as $week)
                @foreach($week as $day)
                    @php
                        $hasWorkouts = $day['workouts']->count() > 0;
                        $hasCompleted = $day['workouts']->contains(fn($w) => $w->completed_at !== null);
                        $hasPlanned = $day['workouts']->contains(fn($w) => $w->completed_at === null && !$day['isPast']);
                        $hasMissed = $day['workouts']->contains(fn($w) => $w->completed_at === null && $day['isPast'] && !$day['isToday']);

                        $cellClass = match (true) {
                            !$day['isCurrentMonth'] => 'invisible',
                            $hasCompleted => 'bg-accent text-zinc-900 dark:text-zinc-900 font-semibold',
                            $hasPlanned => 'bg-zinc-700 dark:bg-zinc-700 text-white dark:text-white',
                            $hasMissed => 'bg-accent/10 dark:bg-accent/10 text-accent/50 dark:text-accent/50',
                            $day['isToday'] && !$hasWorkouts => 'ring-2 ring-accent text-accent font-semibold',
                            default => 'text-zinc-400 dark:text-zinc-500',
                        };

                        $firstWorkout = $day['workouts']->first();
                    @endphp

                    @if($hasWorkouts && $day['isCurrentMonth'])
                        <button
                            type="button"
                            wire:click="$dispatch('show-workout-preview', { workoutId: {{ $firstWorkout->id }} })"
                            class="aspect-square flex items-center justify-center text-sm rounded-lg transition-all hover:scale-105 {{ $cellClass }}"
                        >
                            {{ $day['date']->format('j') }}
                        </button>
                    @else
                        <div class="aspect-square flex items-center justify-center text-sm rounded-lg {{ $cellClass }}">
                            @if($day['isCurrentMonth'])
                                {{ $day['date']->format('j') }}
                            @endif
                        </div>
                    @endif
                @endforeach
            @endforeach
        </div>
    </div>

    {{-- Legend --}}
    <div class="flex items-center justify-center gap-4 text-xs">
        <div class="flex items-center gap-1.5">
            <div class="size-3 rounded-full bg-accent"></div>
            <span class="text-zinc-600 dark:text-zinc-400">Completed</span>
        </div>
        <div class="flex items-center gap-1.5">
            <div class="size-3 rounded-full bg-zinc-700 dark:bg-zinc-700"></div>
            <span class="text-zinc-600 dark:text-zinc-400">Planned</span>
        </div>
        <div class="flex items-center gap-1.5">
            <div class="size-3 rounded-full bg-accent/10 dark:bg-accent/10"></div>
            <span class="text-zinc-600 dark:text-zinc-400">Missed</span>
        </div>
    </div>
</div>
