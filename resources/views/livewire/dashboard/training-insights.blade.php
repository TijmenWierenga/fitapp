<flux:card>
    <flux:heading size="lg">Training Insights</flux:heading>
    <flux:subheading class="mb-4">Last 4 weeks</flux:subheading>

    @if($this->analytics['total_completed'] > 0)
        <div class="grid grid-cols-3 gap-4">
            <div class="flex flex-col items-center gap-1 p-3 rounded-lg bg-zinc-50 dark:bg-zinc-800">
                <flux:heading size="xl" class="font-bold">{{ $this->analytics['total_completed'] }}</flux:heading>
                <flux:text class="text-xs text-zinc-500 dark:text-zinc-400 text-center">Completed</flux:text>
            </div>
            <div class="flex flex-col items-center gap-1 p-3 rounded-lg bg-zinc-50 dark:bg-zinc-800">
                <flux:heading size="xl" class="font-bold">{{ $this->analytics['completion_rate'] }}%</flux:heading>
                <flux:text class="text-xs text-zinc-500 dark:text-zinc-400 text-center">Completion Rate</flux:text>
            </div>
            <div class="flex flex-col items-center gap-1 p-3 rounded-lg bg-zinc-50 dark:bg-zinc-800">
                <flux:heading size="xl" class="font-bold">{{ $this->analytics['current_streak_days'] }}</flux:heading>
                <flux:text class="text-xs text-zinc-500 dark:text-zinc-400 text-center">Streak (days)</flux:text>
            </div>
        </div>
    @else
        <x-empty-state icon="chart-bar" message="No completed workouts yet" />
    @endif
</flux:card>
