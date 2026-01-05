<div class="p-4">
    <form wire:submit="save" class="flex flex-col gap-6">
        <flux:input
            wire:model="name"
            name="name"
            label="Name"
            type="text"
            required
            autofocus
            placeholder="Enter workout name"
        />

        <flux:date-picker
            wire:model="scheduled_date"
            name="scheduled_date"
            label="Scheduled Date"
            required
        />

        <flux:time-picker
            wire:model="scheduled_time"
            name="scheduled_time"
            label="Scheduled Time"
            min="06:00"
            required
        />

        <div class="flex items-center justify-end">
            <flux:button variant="primary" type="submit" class="w-full">
                Save Workout
            </flux:button>
        </div>
    </form>
</div>


