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
                            @foreach($day['workouts'] as $item)
                                @php
                                    $workout = $item['workout'];
                                    $intensityLevel = $item['intensityLevel'];
                                    $isCompleted = $workout->completed_at !== null;
                                    $isOverdue = $day['isPast'] && !$isCompleted;
                                    $isPast = $day['isPast'] || $isCompleted;
                                @endphp
                                <div class="relative group/workout">
                                    <a
                                        href="{{ route('workouts.show', $workout) }}"
                                        class="
                                            block text-xs px-1.5 py-0.5 rounded truncate cursor-pointer transition-all
                                            {{ $intensityLevel->colorClasses() }}
                                            {{ $isPast ? 'opacity-60' : '' }}
                                            hover:ring-2 {{ $intensityLevel->hoverRingClasses() }}
                                        "
                                    >
                                        <span class="flex items-center gap-1">
                                            @if($isCompleted)
                                                <flux:icon.check class="size-3 shrink-0" />
                                            @elseif($isOverdue)
                                                <flux:icon.exclamation-triangle class="size-3 shrink-0" />
                                            @endif
                                            <span class="truncate">{{ $workout->name }}</span>
                                        </span>
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
                                                <div class="mt-1">
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs {{ $intensityLevel->colorClasses() }}">
                                                        {{ $intensityLevel->label() }} Intensity
                                                    </span>
                                                </div>
                                            </div>

                                            <div class="space-y-1 text-sm">
                                                <div class="flex items-center gap-2 text-zinc-600 dark:text-zinc-400">
                                                    <flux:icon.clock class="size-4" />
                                                    <span>{{ $workout->scheduled_at->format('g:i A') }}</span>
                                                </div>

                                                @if($isCompleted)
                                                    <div class="flex items-center gap-2 text-green-600 dark:text-green-400">
                                                        <flux:icon.check-circle class="size-4" />
                                                        <span>Completed {{ $workout->completed_at->format('g:i A') }}</span>
                                                    </div>
                                                @elseif($isOverdue)
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

                                            @if(!$isCompleted && ($day['isToday'] || $day['isPast']))
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
    <div class="mt-4 space-y-2">
        {{-- Intensity levels --}}
        <div class="flex items-center justify-center gap-3 text-xs flex-wrap">
            <span class="text-zinc-500 dark:text-zinc-400 font-medium">Intensity:</span>
            @foreach(\App\Enums\Workout\IntensityLevel::cases() as $level)
                <div class="flex items-center gap-1.5">
                    <div class="size-3 rounded {{ $level->colorClasses() }} border {{ $level->borderColorClasses() }}"></div>
                    <span class="text-zinc-600 dark:text-zinc-400">{{ $level->label() }}</span>
                </div>
            @endforeach
        </div>
        {{-- Status indicators --}}
        <div class="flex items-center justify-center gap-4 text-xs">
            <div class="flex items-center gap-1.5">
                <flux:icon.check class="size-3 text-zinc-600 dark:text-zinc-400" />
                <span class="text-zinc-600 dark:text-zinc-400">Completed</span>
            </div>
            <div class="flex items-center gap-1.5">
                <flux:icon.exclamation-triangle class="size-3 text-zinc-600 dark:text-zinc-400" />
                <span class="text-zinc-600 dark:text-zinc-400">Overdue</span>
            </div>
            <div class="flex items-center gap-1.5">
                <div class="size-3 rounded bg-zinc-300 dark:bg-zinc-600 opacity-60"></div>
                <span class="text-zinc-600 dark:text-zinc-400">Past (faded)</span>
            </div>
        </div>
    </div>
</flux:card>
