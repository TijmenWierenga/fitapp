<x-layouts.app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <livewire:dashboard.next-workout />
            <livewire:dashboard.session-load-overview />
        </div>

        <div class="flex flex-col lg:flex-row gap-6">
            <div class="flex-1 min-w-0">
                <livewire:dashboard.muscle-group-volume />
            </div>
            <div class="w-full lg:w-[360px] shrink-0">
                <livewire:dashboard.workout-calendar />
            </div>
        </div>

        <livewire:dashboard.strength-progression />
    </div>

    <livewire:workout.duplicate />
    <livewire:workout.preview />
    <livewire:exercise.detail />
</x-layouts.app>
