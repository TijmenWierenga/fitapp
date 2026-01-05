<flux:card class="h-full">
    <flux:heading size="lg" class="mb-4">Completed Workouts</flux:heading>

    @if($this->completedWorkouts->count() > 0)
        <div class="flex flex-col gap-3 max-h-96 overflow-y-auto">
            @foreach($this->completedWorkouts as $workout)
                <div class="flex items-center justify-between p-3 rounded-lg border border-zinc-200 dark:border-zinc-700">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <flux:icon.check-circle class="size-5 text-green-500 flex-shrink-0" />
                            <flux:heading size="sm" class="font-semibold truncate">
                                {{ $workout->name }}
                            </flux:heading>
                        </div>
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5 ml-7">
                            Scheduled: {{ $workout->scheduled_at->format('M j, g:i A') }}
                        </flux:text>
                        <flux:text class="text-xs text-zinc-400 dark:text-zinc-500 mt-0.5 ml-7">
                            Completed: {{ $workout->completed_at->format('M j, g:i A') }}
                        </flux:text>
                    </div>
                    <flux:badge color="green" size="sm">
                        {{ $workout->completed_at->diffForHumans() }}
                    </flux:badge>
                </div>
            @endforeach
        </div>

        @if(auth()->user()->workouts()->completed()->count() > 10)
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400 text-center mt-4">
                Showing 10 most recent completed workouts
            </flux:text>
        @endif
    @else
        <div class="flex flex-col items-center justify-center py-8 text-center">
            <flux:icon.check-circle class="size-12 text-zinc-400 dark:text-zinc-600 mb-3" />
            <flux:text class="text-zinc-500 dark:text-zinc-400">
                No completed workouts yet
            </flux:text>
        </div>
    @endif
</flux:card>

