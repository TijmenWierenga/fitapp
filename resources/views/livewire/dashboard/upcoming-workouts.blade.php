<flux:card class="h-full">
    <flux:heading size="lg" class="mb-4">Upcoming Workouts</flux:heading>

    @if($this->upcomingWorkouts->count() > 0)
        <div class="flex flex-col gap-3">
            @foreach($this->upcomingWorkouts as $workout)
                <div class="flex items-center justify-between p-3 rounded-lg border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                    <a href="{{ route('workouts.show', $workout) }}" class="flex-1 min-w-0">
                        <flux:heading size="sm" class="font-semibold truncate hover:text-blue-600 dark:hover:text-blue-400">
                            {{ $workout->name }}
                        </flux:heading>
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">
                            {{ $workout->scheduled_at->format('M j, g:i A') }}
                        </flux:text>
                    </a>
                    <div class="flex items-center gap-2">
                        <flux:badge
                            :color="$workout->scheduled_at->isToday() ? 'green' : ($workout->scheduled_at->isTomorrow() ? 'blue' : 'zinc')"
                            size="sm"
                        >
                            {{ $workout->scheduled_at->diffForHumans() }}
                        </flux:badge>
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
        <div class="flex flex-col items-center justify-center py-8 text-center">
            <flux:icon.calendar class="size-12 text-zinc-400 dark:text-zinc-600 mb-3" />
            <flux:text class="text-zinc-500 dark:text-zinc-400">
                No upcoming workouts
            </flux:text>
        </div>
    @endif
</flux:card>

