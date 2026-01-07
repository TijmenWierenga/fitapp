<div>
    @if($workout)
        <flux:modal name="duplicate-workout" wire:model.live="showModal" @close="closeModal">
            <form wire:submit="save" class="space-y-6">
                <div>
                    <flux:heading size="lg">Duplicate Workout</flux:heading>
                    <flux:text class="mt-1">
                        Create a copy of <strong>{{ $workout->name }}</strong> for a new date and time.
                    </flux:text>
                </div>

                <div class="space-y-4">
                    <flux:date-picker
                        wire:model="scheduled_date"
                        name="scheduled_date"
                        label="Date"
                        required
                    />

                    <flux:time-picker
                        wire:model="scheduled_time"
                        name="scheduled_time"
                        label="Time"
                        required
                    />
                </div>

                <div class="flex gap-2 justify-end">
                    <flux:button type="button" wire:click="closeModal" variant="ghost">
                        Cancel
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        Duplicate Workout
                    </flux:button>
                </div>
            </form>
        </flux:modal>
    @endif
</div>
