<div class="rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6">
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="lg">Your Schedule</flux:heading>

        @if($this->upcomingWorkouts->isNotEmpty())
            <flux:dropdown position="bottom" align="end">
                <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />
                <flux:menu>
                    <flux:menu.item icon="eye" :href="route('workouts.show', $this->upcomingWorkouts->first())">View</flux:menu.item>
                    <flux:menu.item icon="pencil" :href="route('workouts.edit', $this->upcomingWorkouts->first())">Edit</flux:menu.item>
                    <flux:menu.item icon="document-duplicate" wire:click="$dispatch('duplicate-workout', { workoutId: {{ $this->upcomingWorkouts->first()->id }} })">Duplicate</flux:menu.item>
                    <flux:menu.separator />
                    <flux:menu.item variant="danger" icon="trash" wire:click="deleteWorkout({{ $this->upcomingWorkouts->first()->id }})" wire:confirm="Are you sure you want to delete this workout?">Delete</flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        @endif
    </div>

    @if($this->upcomingWorkouts->isNotEmpty())
        <div class="space-y-3 mb-6">
            @foreach($this->upcomingWorkouts as $workout)
                @php
                    $isToday = $workout->scheduled_at->isToday();
                    $isTomorrow = $workout->scheduled_at->isTomorrow();

                    if ($isToday) {
                        $dayLabel = 'Today';
                        $dotClass = 'bg-accent';
                        $nameClass = 'text-white dark:text-white font-medium';
                        $labelClass = 'text-accent font-semibold';
                    } else {
                        if ($isTomorrow) {
                            $dayLabel = 'Tomorrow';
                        } else {
                            $dayLabel = $workout->scheduled_at->format('D');
                        }
                        $dotClass = 'bg-zinc-500 dark:bg-zinc-600';
                        $nameClass = 'text-zinc-400 dark:text-zinc-400';
                        $labelClass = 'text-zinc-500 dark:text-zinc-500';
                    }
                @endphp

                <button
                    type="button"
                    wire:click="$dispatch('show-workout-preview', { workoutId: {{ $workout->id }} })"
                    class="w-full flex items-center gap-3 text-left group"
                >
                    <span class="size-2 rounded-full shrink-0 {{ $dotClass }}"></span>
                    <span class="flex-1 truncate {{ $nameClass }} group-hover:text-accent transition-colors">
                        {{ $workout->name }}
                    </span>
                    <span class="text-sm {{ $labelClass }} shrink-0">{{ $dayLabel }}</span>
                </button>
            @endforeach
        </div>

        <flux:button
            href="{{ route('workouts.create') }}"
            variant="primary"
            class="w-full"
            icon="plus"
        >
            Create Workout
        </flux:button>
    @else
        <div class="text-center py-8">
            <div class="flex justify-center mb-3">
                <flux:icon.calendar class="size-12 text-zinc-300 dark:text-zinc-600" />
            </div>
            <flux:text class="text-zinc-500 dark:text-zinc-400 mb-4">
                No upcoming workouts scheduled
            </flux:text>
            <flux:button
                href="{{ route('workouts.create') }}"
                variant="primary"
                icon="plus"
            >
                Create Workout
            </flux:button>
        </div>
    @endif
</div>
