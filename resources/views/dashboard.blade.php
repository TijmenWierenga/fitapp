<x-layouts.app :title="__('Dashboard')">
    <div class="flex justify-end mb-4 sm:mb-0">
        <flux:button href="{{ route('workouts.create') }}" variant="primary" class="w-full sm:w-auto">
            <span class="sm:inline">Create Workout</span>
            <span class="hidden sm:inline"></span>
        </flux:button>
    </div>

    <div class="flex h-full w-full flex-1 flex-col gap-4 mt-4">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <livewire:dashboard.next-workout />
            <livewire:dashboard.session-load-overview />
        </div>

        <livewire:dashboard.muscle-group-volume />
        <livewire:dashboard.strength-progression />
        <livewire:dashboard.workout-calendar />
    </div>

    <livewire:workout.duplicate />
    <livewire:workout.preview />
    <livewire:exercise.detail />
</x-layouts.app>
