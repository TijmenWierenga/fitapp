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

    <div class="max-w-2xl space-y-4">
        {{-- Activity Type Selector --}}
        <flux:card class="p-4">
            <flux:heading size="sm" class="mb-3">Activity Type</flux:heading>
            <flux:select wire:model="activity">
                @php
                    $grouped = collect(\App\Enums\Workout\Activity::cases())->groupBy(fn ($a) => $a->category());
                    $categoryLabels = [
                        'running' => 'Running',
                        'cycling' => 'Cycling',
                        'swimming' => 'Swimming',
                        'walking' => 'Walking & Hiking',
                        'gym' => 'Gym',
                        'flexibility' => 'Flexibility',
                        'combat' => 'Combat',
                        'racket' => 'Racket Sports',
                        'water' => 'Water Sports',
                        'winter' => 'Winter Sports',
                        'team' => 'Team Sports',
                        'mind_body' => 'Mind & Body',
                        'multi_sport' => 'Multi-Sport',
                        'other' => 'Other',
                    ];
                @endphp
                @foreach($grouped as $category => $activities)
                    <optgroup label="{{ $categoryLabels[$category] ?? ucfirst($category) }}">
                        @foreach($activities as $activityOption)
                            <flux:select.option value="{{ $activityOption->value }}">{{ $activityOption->label() }}</flux:select.option>
                        @endforeach
                    </optgroup>
                @endforeach
            </flux:select>
        </flux:card>

        <flux:card class="p-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <flux:input wire:model="name" label="Workout Name" placeholder="e.g. 5k Interval Session" />
                </div>
                <div>
                    <flux:date-picker wire:model="scheduled_date" label="Date" required />
                </div>
                <div>
                    <flux:time-picker wire:model="scheduled_time" label="Time" required />
                </div>
            </div>

            <flux:separator class="my-4" />

            <flux:field>
                <flux:label>Notes (optional)</flux:label>
                <flux:textarea
                    wire:model="notes"
                    placeholder="Add notes, instructions, or reminders for this workout..."
                    rows="4"
                />
            </flux:field>
        </flux:card>

        <flux:card class="p-6">
            <flux:callout variant="info" icon="information-circle">
                <flux:callout.text>
                    <strong>Workout structure is built via AI tools.</strong> Use the MCP workout tools to add intervals, exercises, rest periods, and more to your workout.
                </flux:callout.text>
            </flux:callout>
        </flux:card>
    </div>
</div>
