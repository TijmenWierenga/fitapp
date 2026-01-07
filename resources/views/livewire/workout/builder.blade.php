<div class="max-w-7xl mx-auto p-6">
    <div class="mb-6">
        <h1 class="text-3xl font-bold mb-2">{{ $workout ? 'Edit' : 'Create' }} Workout</h1>
        <p class="text-gray-600">Build your running workout with custom steps and repeats</p>
    </div>

    <!-- Workout Basic Info -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-xl font-semibold mb-4">Workout Details</h2>
        <form wire:submit="save" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <flux:input
                wire:model="name"
                name="name"
                label="Workout Name"
                type="text"
                required
                placeholder="e.g., Morning Run"
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
                required
            />

            <div class="col-span-full flex gap-2">
                <flux:button variant="primary" type="submit">
                    Save Workout Details
                </flux:button>
            </div>
        </form>
    </div>

    @if($workout)
        <!-- Steps Section -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold">Workout Steps</h2>
                <div class="flex gap-2">
                    <flux:button wire:click="openStepModal()" size="sm" variant="primary">
                        + Add Step
                    </flux:button>
                    <flux:button wire:click="addDefaultRepeat" size="sm">
                        + Add Repeat Block
                    </flux:button>
                </div>
            </div>

            @if($steps->isEmpty())
                <div class="text-center py-8 text-gray-500">
                    <p>No steps added yet. Start building your workout!</p>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($steps as $index => $step)
                        <div class="border border-gray-300 rounded-lg p-4">
                            @if($step->isRepeat())
                                <!-- Repeat Block -->
                                <div class="bg-blue-50 border border-blue-200 rounded p-3">
                                    <div class="flex justify-between items-start mb-3">
                                        <div>
                                            <h3 class="font-semibold text-blue-900">
                                                Repeat {{ $step->repeat_count }}x
                                                @if($step->name)
                                                    - {{ $step->name }}
                                                @endif
                                            </h3>
                                            @if($step->skip_last_recovery)
                                                <span class="text-xs text-blue-600">Skip last recovery</span>
                                            @endif
                                        </div>
                                        <div class="flex gap-1">
                                            <flux:button wire:click="openStepModal({{ $step->id }})" size="xs" variant="ghost">
                                                + Add Child
                                            </flux:button>
                                            <flux:button wire:click="openStepModal(null, {{ $step->id }})" size="xs" variant="ghost">
                                                Edit
                                            </flux:button>
                                            <flux:button wire:click="deleteStep({{ $step->id }})" wire:confirm="Are you sure?" size="xs" variant="ghost">
                                                Delete
                                            </flux:button>
                                        </div>
                                    </div>

                                    <!-- Child Steps -->
                                    <div class="ml-4 space-y-2">
                                        @foreach($step->children as $child)
                                            @include('livewire.workout.partials.step-card', ['step' => $child, 'isChild' => true])
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <!-- Normal Step -->
                                @include('livewire.workout.partials.step-card', ['step' => $step, 'isChild' => false])
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @else
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-yellow-800">
            <p class="font-medium">Save workout details first to start adding steps.</p>
        </div>
    @endif

    <!-- Step Modal -->
    @if($showStepModal)
        <flux:modal name="step-modal" wire:model="showStepModal">
            <form wire:submit="saveStep" class="space-y-4">
                <h3 class="text-xl font-bold mb-4">
                    {{ $editingStepId ? 'Edit' : 'Add' }} Step
                    @if($parentStepId)
                        <span class="text-sm font-normal text-gray-600">(inside repeat block)</span>
                    @endif
                </h3>

                <!-- Step Kind -->
                <flux:select wire:model.live="step_kind" label="Step Type" name="step_kind">
                    @if(!$parentStepId)
                        <option value="warmup">Warm Up</option>
                    @endif
                    <option value="run">Run</option>
                    <option value="recovery">Recovery</option>
                    @if(!$parentStepId)
                        <option value="cooldown">Cool Down</option>
                        <option value="repeat">Repeat Block</option>
                    @endif
                </flux:select>

                <!-- Step Name (optional) -->
                <flux:input
                    wire:model="step_name"
                    label="Step Name (Optional)"
                    placeholder="e.g., Interval 1"
                />

                @if($step_kind !== 'repeat')
                    <!-- Duration Type -->
                    <flux:radio.group wire:model.live="duration_type" label="Duration" variant="segmented">
                        <flux:radio value="time" label="Time" />
                        <flux:radio value="distance" label="Distance" />
                        <flux:radio value="lap_press" label="Lap Press" />
                    </flux:radio.group>

                    @if($duration_type === 'time')
                        <div class="grid grid-cols-2 gap-4">
                            <flux:input
                                wire:model="duration_minutes"
                                type="number"
                                min="0"
                                label="Minutes"
                            />
                            <flux:input
                                wire:model="duration_seconds"
                                type="number"
                                min="0"
                                max="59"
                                label="Seconds"
                            />
                        </div>
                    @elseif($duration_type === 'distance')
                        <div class="grid grid-cols-2 gap-4">
                            <flux:input
                                wire:model="duration_km"
                                type="number"
                                min="0"
                                label="Kilometers"
                            />
                            <flux:input
                                wire:model="duration_tens_of_meters"
                                type="number"
                                min="0"
                                max="99"
                                label="Tens of Meters (0-99)"
                            />
                        </div>
                        <p class="text-sm text-gray-600">
                            Total: {{ number_format(($duration_km * 1000 + $duration_tens_of_meters * 10) / 1000, 3) }} km
                        </p>
                    @else
                        <p class="text-sm text-gray-600">Step completes when you press the lap button on your device.</p>
                    @endif

                    <!-- Target Type -->
                    <flux:radio.group wire:model.live="target_type" label="Target" variant="segmented">
                        <flux:radio value="none" label="No Target" />
                        <flux:radio value="heart_rate" label="Heart Rate" />
                        <flux:radio value="pace" label="Pace" />
                    </flux:radio.group>

                    @if($target_type === 'heart_rate')
                        <flux:radio.group wire:model.live="target_mode" label="HR Target Mode" variant="segmented">
                            <flux:radio value="zone" label="Zone" />
                            <flux:radio value="range" label="Range" />
                        </flux:radio.group>

                        @if($target_mode === 'zone')
                            <flux:select wire:model="target_zone" label="HR Zone">
                                <option value="1">Zone 1</option>
                                <option value="2">Zone 2</option>
                                <option value="3">Zone 3</option>
                                <option value="4">Zone 4</option>
                                <option value="5">Zone 5</option>
                            </flux:select>
                        @elseif($target_mode === 'range')
                            <div class="grid grid-cols-2 gap-4">
                                <flux:input
                                    wire:model="target_low_bpm"
                                    type="number"
                                    min="40"
                                    max="230"
                                    label="Low (bpm)"
                                />
                                <flux:input
                                    wire:model="target_high_bpm"
                                    type="number"
                                    min="40"
                                    max="230"
                                    label="High (bpm)"
                                />
                            </div>
                        @endif
                    @elseif($target_type === 'pace')
                        <flux:radio.group wire:model.live="target_mode" label="Pace Target Mode" variant="segmented">
                            <flux:radio value="zone" label="Zone" />
                            <flux:radio value="range" label="Range" />
                        </flux:radio.group>

                        @if($target_mode === 'zone')
                            <flux:select wire:model="target_zone" label="Pace Zone">
                                <option value="1">Zone 1</option>
                                <option value="2">Zone 2</option>
                                <option value="3">Zone 3</option>
                                <option value="4">Zone 4</option>
                                <option value="5">Zone 5</option>
                            </flux:select>
                        @elseif($target_mode === 'range')
                            <div class="space-y-2">
                                <label class="block text-sm font-medium">Low Pace (min/km)</label>
                                <div class="grid grid-cols-2 gap-4">
                                    <flux:input
                                        wire:model="target_low_minutes"
                                        type="number"
                                        min="0"
                                        label="Minutes"
                                    />
                                    <flux:input
                                        wire:model="target_low_seconds"
                                        type="number"
                                        min="0"
                                        max="59"
                                        label="Seconds"
                                    />
                                </div>
                                <label class="block text-sm font-medium mt-4">High Pace (min/km)</label>
                                <div class="grid grid-cols-2 gap-4">
                                    <flux:input
                                        wire:model="target_high_minutes"
                                        type="number"
                                        min="0"
                                        label="Minutes"
                                    />
                                    <flux:input
                                        wire:model="target_high_seconds"
                                        type="number"
                                        min="0"
                                        max="59"
                                        label="Seconds"
                                    />
                                </div>
                            </div>
                        @endif
                    @endif

                    <!-- Notes -->
                    <flux:textarea
                        wire:model="notes"
                        label="Notes (Optional)"
                        rows="2"
                    />
                @else
                    <!-- Repeat Block Settings -->
                    <flux:input
                        wire:model="repeat_count"
                        type="number"
                        min="2"
                        label="Repeat Count"
                    />

                    <flux:checkbox
                        wire:model="skip_last_recovery"
                        label="Skip last recovery"
                    />

                    <p class="text-sm text-gray-600">
                        After creating the repeat block, add child steps to define what gets repeated.
                    </p>
                @endif

                <div class="flex gap-2 justify-end pt-4">
                    <flux:button wire:click="closeStepModal" variant="ghost">
                        Cancel
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        {{ $editingStepId ? 'Update' : 'Add' }} Step
                    </flux:button>
                </div>
            </form>
        </flux:modal>
    @endif
</div>
