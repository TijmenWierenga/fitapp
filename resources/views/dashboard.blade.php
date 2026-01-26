<x-layouts.app :title="__('Dashboard')">
    <div class="flex justify-end mb-4 sm:mb-0">
        <flux:button href="{{ route('workouts.create') }}" variant="primary" class="w-full sm:w-auto">
            <span class="sm:inline">Create Workout</span>
            <span class="hidden sm:inline"></span>
        </flux:button>
    </div>

    <div class="flex h-full w-full flex-1 flex-col gap-4 mt-4">
        <div class="grid gap-4 lg:grid-cols-3">
            <div class="relative overflow-hidden rounded-xl lg:col-span-3 xl:col-span-1">
                <livewire:dashboard.next-workout />
            </div>
            <div class="relative overflow-hidden rounded-xl lg:col-span-3 xl:col-span-1">
                <livewire:dashboard.upcoming-workouts />
            </div>
            <div class="relative overflow-hidden rounded-xl lg:col-span-3 xl:col-span-1">
                <livewire:dashboard.completed-workouts />
            </div>
        </div>

        <div class="relative overflow-hidden rounded-xl">
            <livewire:dashboard.workout-calendar />
        </div>
    </div>

    <livewire:workout.duplicate />
</x-layouts.app>
