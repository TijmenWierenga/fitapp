<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <flux:heading size="xl">
            {{ $workout && $workout->exists ? 'Edit Workout' : 'Create Workout' }}
        </flux:heading>
        <div class="flex gap-2">
            <flux:button href="{{ route('dashboard') }}" variant="ghost">Cancel</flux:button>
            <flux:button variant="primary" wire:click="saveWorkout">Save Workout</flux:button>
        </div>
    </div>

    <div class="max-w-2xl space-y-6">
        {{-- Activity Type Selector --}}
        <flux:card class="p-4">
            <flux:heading size="sm" class="mb-3">Activity Type</flux:heading>
            <flux:select value="{{ $activity->value }}" wire:change="selectActivity($event.target.value)">
                @php
                    $grouped = collect(\App\Enums\Workout\Activity::cases())->groupBy(fn ($a) => $a->category());
                @endphp
                @foreach($grouped as $category => $activities)
                    <optgroup label="{{ ucfirst(str_replace('_', ' ', $category)) }}">
                        @foreach($activities as $a)
                            <option value="{{ $a->value }}">{{ $a->label() }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </flux:select>
        </flux:card>

        {{-- Workout Metadata --}}
        <flux:card class="p-4">
            <flux:heading size="sm" class="mb-3">Workout Details</flux:heading>
            <div class="space-y-4">
                <flux:input wire:model="name" label="Workout Name" placeholder="e.g., Morning Run" />
                <div class="grid grid-cols-2 gap-4">
                    <flux:input type="date" wire:model="scheduled_date" label="Date" />
                    <flux:input type="time" wire:model="scheduled_time" label="Time" />
                </div>
                <flux:textarea wire:model="notes" label="Notes (Markdown)" placeholder="Optional workout notes..." rows="4" />
            </div>
        </flux:card>
    </div>
</div>
