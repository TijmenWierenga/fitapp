<flux:card class="h-full">
    <flux:heading size="lg" class="mb-4">Next Workout</flux:heading>

    @if($this->nextWorkout)
        <div class="flex flex-col gap-4">
            <div>
                <flux:heading size="xl" class="font-bold">{{ $this->nextWorkout->name }}</flux:heading>
                <flux:text class="text-zinc-500 dark:text-zinc-400 mt-1">
                    {{ $this->nextWorkout->scheduled_at->format('l, F j, Y') }}
                </flux:text>
                <flux:text class="text-zinc-500 dark:text-zinc-400">
                    {{ $this->nextWorkout->scheduled_at->format('g:i A') }}
                </flux:text>
            </div>

            <div class="flex flex-col gap-2">
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-300">
                    @if($this->nextWorkout->scheduled_at->isToday())
                        <flux:badge color="green" size="sm">Today</flux:badge>
                    @elseif($this->nextWorkout->scheduled_at->isTomorrow())
                        <flux:badge color="blue" size="sm">Tomorrow</flux:badge>
                    @else
                        <flux:badge color="zinc" size="sm">
                            {{ $this->nextWorkout->scheduled_at->diffForHumans() }}
                        </flux:badge>
                    @endif
                </flux:text>
            </div>

            <div class="mt-auto pt-4">
                <flux:button
                    wire:click="markAsCompleted({{ $this->nextWorkout->id }})"
                    variant="primary"
                    class="w-full"
                >
                    Mark as Completed
                </flux:button>
            </div>
        </div>
    @else
        <div class="flex flex-col items-center justify-center py-8 text-center">
            <flux:icon.calendar class="size-12 text-zinc-400 dark:text-zinc-600 mb-3" />
            <flux:text class="text-zinc-500 dark:text-zinc-400">
                No upcoming workouts scheduled
            </flux:text>
            <flux:button
                href="{{ route('workouts.create') }}"
                variant="primary"
                class="mt-4"
            >
                Schedule Workout
            </flux:button>
        </div>
    @endif
</flux:card>

