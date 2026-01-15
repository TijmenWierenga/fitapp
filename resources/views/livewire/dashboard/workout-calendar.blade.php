<flux:card class="h-full">
    <div class="flex items-center justify-between mb-4">
        <flux:heading size="lg">Workout Calendar</flux:heading>
        <div class="flex items-center gap-2">
            <flux:button wire:click="previousMonth" variant="ghost" size="sm" icon="chevron-left" />
            <flux:button wire:click="today" variant="ghost" size="sm">
                {{ $this->monthName }}
            </flux:button>
            <flux:button wire:click="nextMonth" variant="ghost" size="sm" icon="chevron-right" />
        </div>
    </div>

    <div class="grid grid-cols-7 gap-px bg-zinc-200 dark:bg-zinc-700 rounded-lg overflow-hidden border border-zinc-200 dark:border-zinc-700">
        {{-- Day headers --}}
        @foreach(['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $day)
            <div class="bg-zinc-50 dark:bg-zinc-800 p-2 text-center text-xs font-semibold text-zinc-600 dark:text-zinc-400">
                {{ $day }}
            </div>
        @endforeach

        {{-- Calendar days --}}
        @foreach($this->calendarWeeks as $week)
            @foreach($week as $day)
                <div class="
                    bg-white dark:bg-zinc-900 p-2 min-h-24 relative group
                    {{ !$day['isCurrentMonth'] ? 'opacity-40' : '' }}
                    {{ $day['isToday'] ? 'ring-2 ring-inset ring-blue-500' : '' }}
                ">
                    <div class="text-sm font-medium mb-1 {{ $day['isToday'] ? 'text-blue-600 dark:text-blue-400' : 'text-zinc-700 dark:text-zinc-300' }}">
                        {{ $day['date']->format('j') }}
                    </div>

                    @if($day['workouts']->count() > 0)
                        <div class="space-y-1">
                            @foreach($day['workouts'] as $workout)
                                <div class="relative group/workout">
                                    <a
                                        href="{{ route('workouts.show', $workout) }}"
                                        class="
                                            block text-xs px-1.5 py-0.5 rounded truncate cursor-pointer transition-all
                                            {{ $workout->completed_at ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : ($day['isPast'] ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400') }}
                                            hover:ring-2 {{ $workout->completed_at ? 'hover:ring-green-500' : ($day['isPast'] ? 'hover:ring-red-500' : 'hover:ring-blue-500') }}
                                        "
                                    >
                                        {{ $workout->name }}
                                    </a>

                                    {{-- Tooltip on hover --}}
                                    <div class="
                                        absolute z-50 left-0 top-full w-64 pt-1
                                        opacity-0 invisible group-hover/workout:opacity-100 group-hover/workout:visible
                                        transition-all duration-200
                                    ">
                                        <div class="p-3 rounded-lg shadow-lg border bg-white dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700 space-y-2">
                                            <div>
                                                <flux:heading size="sm" class="font-semibold">
                                                    {{ $workout->name }}
                                                </flux:heading>
                                            </div>

                                            <div class="space-y-1 text-sm">
                                                <div class="flex items-center gap-2 text-zinc-600 dark:text-zinc-400">
                                                    <flux:icon.clock class="size-4" />
                                                    <span>{{ $workout->scheduled_at->format('g:i A') }}</span>
                                                </div>

                                                @if($workout->completed_at)
                                                    <div class="flex items-center gap-2 text-green-600 dark:text-green-400">
                                                        <flux:icon.check-circle class="size-4" />
                                                        <span>Completed {{ $workout->completed_at->format('g:i A') }}</span>
                                                    </div>
                                                @elseif($day['isPast'])
                                                    <div class="flex items-center gap-2 text-red-600 dark:text-red-400">
                                                        <flux:icon.exclamation-circle class="size-4" />
                                                        <span>Overdue</span>
                                                    </div>
                                                @else
                                                    <div class="flex items-center gap-2 text-blue-600 dark:text-blue-400">
                                                        <flux:icon.calendar class="size-4" />
                                                        <span>{{ $workout->scheduled_at->diffForHumans() }}</span>
                                                    </div>
                                                @endif
                                            </div>

                                            @if(!$workout->completed_at && ($day['isToday'] || $day['isPast']))
                                                <div class="pt-2 border-t border-zinc-200 dark:border-zinc-700">
                                                    <flux:button
                                                        wire:click="$dispatch('mark-workout-complete', { workoutId: {{ $workout->id }} })"
                                                        variant="primary"
                                                        size="xs"
                                                        class="w-full"
                                                    >
                                                        Mark Complete
                                                    </flux:button>
                                                </div>
                                            @endif

                                            <div class="pt-2 border-t border-zinc-200 dark:border-zinc-700">
                                                <flux:button
                                                    href="{{ route('workouts.show', $workout) }}"
                                                    variant="ghost"
                                                    size="xs"
                                                    class="w-full"
                                                >
                                                    View Details
                                                </flux:button>
                                            </div>

                                            @if($workout->canBeEdited())
                                                <div class="pt-2 border-t border-zinc-200 dark:border-zinc-700">
                                                    <flux:button
                                                        href="{{ route('workouts.edit', $workout) }}"
                                                        variant="ghost"
                                                        size="xs"
                                                        class="w-full"
                                                    >
                                                        Edit Workout
                                                    </flux:button>
                                                </div>
                                            @endif

                                            <div class="pt-2 border-t border-zinc-200 dark:border-zinc-700">
                                                <flux:button
                                                    wire:click="$dispatch('duplicate-workout', { workoutId: {{ $workout->id }} })"
                                                    variant="ghost"
                                                    size="xs"
                                                    class="w-full"
                                                >
                                                    Duplicate
                                                </flux:button>
                                            </div>

                                            @if(!$day['isPast'] || $day['isToday'])
                                                <div class="pt-2 border-t border-zinc-200 dark:border-zinc-700">
                                                    <flux:button
                                                        wire:click="deleteWorkout({{ $workout->id }})"
                                                        wire:confirm="Are you sure you want to delete this workout?"
                                                        variant="danger"
                                                        size="xs"
                                                        class="w-full"
                                                    >
                                                        Delete Workout
                                                    </flux:button>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @if($day['workouts']->count() > 3)
                            <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                                +{{ $day['workouts']->count() - 3 }} more
                            </div>
                        @endif
                    @endif
                </div>
            @endforeach
        @endforeach
    </div>

    {{-- Legend --}}
    <div class="flex items-center justify-center gap-4 mt-4 text-xs">
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

