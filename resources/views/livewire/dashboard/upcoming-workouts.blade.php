<flux:card class="h-full">
    <flux:heading size="lg" class="mb-4">Upcoming Workouts</flux:heading>

    @if($this->upcomingWorkouts->count() > 0)
        <div class="flex flex-col gap-3">
            @foreach($this->upcomingWorkouts as $workout)
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 p-3 rounded-lg border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                    <a href="{{ route('workouts.show', $workout) }}" class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2 mb-1">
                            <flux:heading size="sm" class="font-semibold truncate hover:text-blue-600 dark:hover:text-blue-400">
                                {{ $workout->name }}
                            </flux:heading>
                            <x-activity-badge :activity="$workout->activity" />
                        </div>
                        <flux:text class="text-xs sm:text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $workout->scheduled_at->format('M j, g:i A') }}
                        </flux:text>
                    </a>
                    <div class="flex items-center gap-2 self-end sm:self-auto">
                        <x-workout-schedule-badge :scheduled-at="$workout->scheduled_at" />
                        <flux:dropdown position="bottom" align="end">
                            <flux:button variant="ghost" size="xs" icon="ellipsis-vertical" />
                            <flux:menu>
                                <flux:menu.item
                                    href="{{ route('workouts.show', $workout) }}"
                                    icon="eye"
                                >
                                    View
                                </flux:menu.item>
                                @if($workout->canBeEdited())
                                    <flux:menu.item
                                        href="{{ route('workouts.edit', $workout) }}"
                                        icon="pencil-square"
                                    >
                                        Edit
                                    </flux:menu.item>
                                @endif
                                <flux:menu.item
                                    wire:click="$dispatch('duplicate-workout', { workoutId: {{ $workout->id }} })"
                                    icon="document-duplicate"
                                >
                                    Duplicate
                                </flux:menu.item>
                                <flux:menu.item
                                    wire:click="deleteWorkout({{ $workout->id }})"
                                    wire:confirm="Are you sure you want to delete this workout?"
                                    icon="trash"
                                >
                                    Delete
                                </flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <x-empty-state icon="calendar" message="No upcoming workouts" />
    @endif
</flux:card>

