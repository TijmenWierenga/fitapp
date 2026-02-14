<x-layouts.app :title="__('Dashboard')">
    <div class="flex justify-end mb-4 sm:mb-0">
        <flux:button href="{{ route('workouts.create') }}" variant="primary" class="w-full sm:w-auto">
            <span class="sm:inline">Create Workout</span>
            <span class="hidden sm:inline"></span>
        </flux:button>
    </div>

    <div class="flex h-full w-full flex-1 flex-col gap-4 mt-4">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="relative overflow-hidden rounded-xl">
                <livewire:dashboard.next-workout />
            </div>

            <div class="relative overflow-hidden rounded-xl">
                <livewire:dashboard.workload-overview />
            </div>
        </div>

        <div class="relative overflow-hidden rounded-xl">
            <livewire:dashboard.workout-calendar />
        </div>
    </div>

    <livewire:workout.duplicate />
</x-layouts.app>
