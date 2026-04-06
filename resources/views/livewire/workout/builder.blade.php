<div class="p-6">
    @if($importContextKey)
        <flux:callout variant="info" class="mb-6">
            <flux:callout.heading>Importing from Garmin FIT</flux:callout.heading>
            <flux:callout.text>Review the workout structure and save to complete the import.</flux:callout.text>
        </flux:callout>
    @endif

    @if(session('error'))
        <flux:callout variant="danger" class="mb-6">
            <flux:callout.heading>Error</flux:callout.heading>
            <flux:callout.text>{{ session('error') }}</flux:callout.text>
        </flux:callout>
    @endif

    @error('importContextKey')
        <flux:callout variant="danger" class="mb-6">
            <flux:callout.heading>Import expired</flux:callout.heading>
            <flux:callout.text>{{ $message }}</flux:callout.text>
        </flux:callout>
    @enderror

    <div class="flex justify-between items-center mb-6">
        <flux:heading size="xl">
            @if($importContextKey)
                Import Workout
            @elseif($workout && $workout->exists)
                Edit Workout
            @else
                Create Workout
            @endif
        </flux:heading>
        <div class="flex gap-2">
            <flux:button href="{{ route('dashboard') }}" variant="ghost">Cancel</flux:button>
            <flux:button variant="primary" wire:click="saveWorkout">
                {{ $importContextKey ? 'Save & Complete' : 'Save Workout' }}
            </flux:button>
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

        {{-- RPE / Feeling (import mode only) --}}
        @if($importContextKey)
            <flux:card class="p-4">
                <flux:heading size="sm" class="mb-3">How did it go?</flux:heading>

                <div class="space-y-6">
                    {{-- RPE --}}
                    <flux:field>
                        <flux:label>Rate of Perceived Exertion (RPE)</flux:label>
                        <flux:description>How hard did this workout feel?</flux:description>
                        <div class="mt-3">
                            <x-numeric-scale :min="1" :max="10" wire="rpe" :selected="$rpe" />
                            <div class="flex justify-between mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                                <span>Very Easy</span>
                                <span>Easy</span>
                                <span>Moderate</span>
                                <span>Hard</span>
                                <span>Maximum</span>
                            </div>
                        </div>
                        <flux:error name="rpe" />
                    </flux:field>

                    {{-- Feeling --}}
                    <flux:field>
                        <flux:label>Overall Feeling</flux:label>
                        <flux:description>How did you feel during this workout?</flux:description>
                        <div class="mt-3">
                            @php($feelingScale = \App\Models\Workout::feelingScale())
                            <div class="flex justify-between gap-2">
                                @foreach($feelingScale as $value => $data)
                                    <button
                                        type="button"
                                        wire:click="$set('feeling', {{ $value }})"
                                        class="flex-1 py-3 text-3xl rounded-md transition-colors {{ $feeling === $value ? 'bg-accent' : 'bg-zinc-100 dark:bg-zinc-700 hover:bg-zinc-200 dark:hover:bg-zinc-600' }}"
                                    >
                                        {{ $data['emoji'] }}
                                    </button>
                                @endforeach
                            </div>
                            <div class="flex justify-between mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                                @foreach($feelingScale as $data)
                                    <span class="flex-1 text-center">{{ $data['label'] }}</span>
                                @endforeach
                            </div>
                        </div>
                        <flux:error name="feeling" />
                    </flux:field>
                </div>
            </flux:card>
        @endif
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
