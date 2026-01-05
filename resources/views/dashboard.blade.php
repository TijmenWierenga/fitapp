<x-layouts.app :title="__('Dashboard')">
    <div class="flex justify-end">
        <flux:button href="{{ route('workouts.create') }}" variant="primary">
            Create Workout
        </flux:button>
    </div>

    <div class="flex h-full w-full flex-1 flex-col gap-4 mt-4">
        <div class="grid gap-4 md:grid-cols-3">
            <div class="relative overflow-hidden rounded-xl">
                <livewire:dashboard.next-workout />
            </div>
            <div class="relative overflow-hidden rounded-xl">
                <livewire:dashboard.upcoming-workouts />
            </div>
            <div class="relative overflow-hidden rounded-xl">
                <livewire:dashboard.completed-workouts />
            </div>
        </div>
    </div>
</x-layouts.app>
