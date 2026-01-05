<flux:card class="h-full">
    <flux:heading size="lg" class="mb-4">Upcoming Workouts</flux:heading>

    @if($this->upcomingWorkouts->count() > 0)
        <div class="flex flex-col gap-3">
            @foreach($this->upcomingWorkouts as $workout)
                <div class="flex items-center justify-between p-3 rounded-lg border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                    <div class="flex-1 min-w-0">
                        <flux:heading size="sm" class="font-semibold truncate">
                            {{ $workout->name }}
                        </flux:heading>
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">
                            {{ $workout->scheduled_at->format('M j, g:i A') }}
                        </flux:text>
                    </div>
                    <flux:badge
                        :color="$workout->scheduled_at->isToday() ? 'green' : ($workout->scheduled_at->isTomorrow() ? 'blue' : 'zinc')"
                        size="sm"
                    >
                        {{ $workout->scheduled_at->diffForHumans() }}
                    </flux:badge>
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

