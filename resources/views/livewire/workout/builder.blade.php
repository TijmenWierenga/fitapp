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

    {{-- Workout Structure Editor --}}
    <div class="max-w-3xl mt-6 space-y-3">
        <div class="flex items-center justify-between">
            <flux:heading size="lg">Workout Structure</flux:heading>
            <flux:button wire:click="addSection" variant="primary" size="sm" icon="plus">
                Add Section
            </flux:button>
        </div>

        @if(count($sections) > 0)
            <div wire:sort="sortSections" class="space-y-2">
                @foreach($sections as $si => $section)
                    <div wire:key="{{ $section['_key'] }}" wire:sort:item="{{ $section['_key'] }}">
                        @include('livewire.workout.partials.section-editor', [
                            'section' => $section,
                            'si' => $si,
                        ])
                    </div>
                @endforeach
            </div>
        @else
            <div class="rounded-lg border border-dashed border-zinc-300 dark:border-zinc-600 p-6 text-center">
                <flux:text class="text-sm text-zinc-400 dark:text-zinc-500">
                    No structure yet. Add a section to get started.
                </flux:text>
                <flux:button wire:click="addSection" variant="subtle" size="sm" icon="plus" class="mt-3">
                    Add Section
                </flux:button>
            </div>
        @endif
    </div>

    <livewire:workout.exercise-search />
</div>
