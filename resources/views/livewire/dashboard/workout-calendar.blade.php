<flux:card class="h-full">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
        <flux:heading size="lg" class="text-base sm:text-lg">Workout Calendar</flux:heading>
        <div class="flex items-center gap-2">
            <flux:button wire:click="previousMonth" variant="ghost" size="sm" icon="chevron-left" aria-label="Previous month" />
            <flux:button wire:click="today" variant="ghost" size="sm" class="min-w-24 sm:min-w-32">
                <span class="truncate">{{ $this->monthName }}</span>
            </flux:button>
            <flux:button wire:click="nextMonth" variant="ghost" size="sm" icon="chevron-right" aria-label="Next month" />
        </div>
    </div>

    <div class="grid grid-cols-7 gap-px bg-zinc-200 dark:bg-zinc-700 rounded-lg overflow-hidden">
        {{-- Day headers --}}
        @foreach(['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $index => $day)
            <div class="bg-zinc-50 dark:bg-zinc-800 p-1 sm:p-2 text-center text-xs font-semibold text-zinc-600 dark:text-zinc-400">
                <span class="sm:hidden">{{ substr($day, 0, 1) }}</span>
                <span class="hidden! sm:inline!">{{ $day }}</span>
            </div>
        @endforeach

        {{-- Calendar days --}}
        @foreach($this->calendarWeeks as $week)
            @foreach($week as $day)
                <div class="
                    bg-white dark:bg-zinc-900 p-1 sm:p-2 min-h-16 sm:min-h-20 md:min-h-24 relative group
                    {{ !$day['isCurrentMonth'] ? 'opacity-40' : '' }}
                    {{ $day['isToday'] ? 'ring-2 ring-inset ring-blue-500' : '' }}
                ">
                    <div class="text-xs sm:text-sm font-medium mb-0.5 sm:mb-1 {{ $day['isToday'] ? 'text-blue-600 dark:text-blue-400' : 'text-zinc-700 dark:text-zinc-300' }}">
                        {{ $day['date']->format('j') }}
                    </div>

                    @if($day['workouts']->count() > 0)
                        <div class="space-y-0.5 sm:space-y-1">
                            @foreach($day['workouts']->take(3) as $workout)
                                <button
                                    type="button"
                                    wire:click="$dispatch('show-workout-preview', { workoutId: {{ $workout->id }} })"
                                    class="
                                        block w-full text-left text-[0.625rem] sm:text-xs px-1 sm:px-1.5 py-0.5 rounded truncate cursor-pointer transition-all
                                        {{ $workout->completed_at ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : ($day['isPast'] ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400') }}
                                        hover:ring-1 sm:hover:ring-2 {{ $workout->completed_at ? 'hover:ring-green-500' : ($day['isPast'] ? 'hover:ring-red-500' : 'hover:ring-blue-500') }}
                                    "
                                >
                                    {{ $workout->name }}
                                </button>
                            @endforeach
                        </div>

                        @if($day['workouts']->count() > 3)
                            <div class="text-[0.625rem] sm:text-xs text-zinc-500 dark:text-zinc-400 mt-0.5 sm:mt-1 px-1">
                                <span class="hidden sm:inline">+{{ $day['workouts']->count() - 3 }} more</span>
                                <span class="sm:hidden">+{{ $day['workouts']->count() - 3 }}</span>
                            </div>
                        @endif
                    @endif
                </div>
            @endforeach
        @endforeach
    </div>

    {{-- Legend --}}
    <div class="flex flex-wrap items-center justify-center gap-3 sm:gap-4 mt-4 text-xs">
        <div class="flex items-center gap-1.5">
            <div class="size-3 rounded bg-blue-100 dark:bg-blue-900/30 border border-blue-300 dark:border-blue-700"></div>
            <span class="text-zinc-600 dark:text-zinc-400">Upcoming</span>
        </div>
        <div class="flex items-center gap-1.5">
            <div class="size-3 rounded bg-green-100 dark:bg-green-900/30 border border-green-300 dark:border-green-700"></div>
            <span class="text-zinc-600 dark:text-zinc-400">Completed</span>
        </div>
        <div class="flex items-center gap-1.5">
            <div class="size-3 rounded bg-red-100 dark:bg-red-900/30 border border-red-300 dark:border-red-700"></div>
            <span class="text-zinc-600 dark:text-zinc-400">Overdue</span>
        </div>
    </div>
</flux:card>

