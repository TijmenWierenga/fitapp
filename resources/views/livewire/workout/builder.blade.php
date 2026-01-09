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

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left: Steps Tree -->
        <div class="lg:col-span-2 space-y-4">
            <flux:card class="p-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-1">
                        <flux:input wire:model="name" label="Workout Name" placeholder="e.g. 5k Interval Session" />
                    </div>
                    <div>
                        <flux:date-picker wire:model="scheduled_date" label="Date" required />
                    </div>
                    <div>
                        <flux:time-picker wire:model="scheduled_time" label="Time" required />
                    </div>
                </div>
            </flux:card>

            <div class="space-y-4">
                @foreach ($steps as $index => $step)
                    <div wire:key="step-{{ $index }}">
                        @if ($step['step_kind'] === \App\Enums\Workout\StepKind::Repeat->value)
                            <div class="border-2 border-dashed border-zinc-200 dark:border-zinc-700 rounded-xl p-4 bg-zinc-50/50 dark:bg-zinc-800/50">
                                <div class="flex justify-between items-center mb-4">
                                    <div class="flex items-center gap-2">
                                        <flux:icon.arrow-path class="size-5 text-zinc-500" />
                                        <flux:heading size="sm">Repeat {{ $step['repeat_count'] }}x</flux:heading>
                                        @if($step['skip_last_recovery'])
                                            <flux:badge size="sm">Skip last recovery</flux:badge>
                                        @endif
                                    </div>
                                    <div class="flex gap-2">
                                        <flux:button size="xs" variant="ghost" wire:click="moveUp('{{ $index }}')" :disabled="$loop->first" icon="chevron-up" />
                                        <flux:button size="xs" variant="ghost" wire:click="moveDown('{{ $index }}')" :disabled="$loop->last" icon="chevron-down" />
                                        <flux:button size="xs" variant="ghost" wire:click="editStep('{{ $index }}')">Edit</flux:button>
                                        <flux:button size="xs" variant="ghost" wire:click="removeStep('{{ $index }}')" class="text-red-500" icon="trash" />
                                    </div>
                                </div>

                                <div class="pl-6 space-y-2 border-l-2 border-zinc-200 dark:border-zinc-700 ml-2">
                                    @foreach ($step['children'] as $childIndex => $child)
                                        <div wire:key="step-{{ $index }}-{{ $childIndex }}">
                                            <x-step-card :step="$child" :path="$index.'.children.'.$childIndex" :loop="$loop" />
                                        </div>
                                    @endforeach
                                    <flux:button size="sm" variant="ghost" wire:click="addStep({{ $index }})" icon="plus" class="w-full justify-start">
                                        Add Step to Repeat
                                    </flux:button>
                                </div>
                            </div>
                        @else
                            <x-step-card :step="$step" :path="(string)$index" :loop="$loop" />
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Right: Toolbox -->
        <div class="space-y-4">
            <flux:card class="p-4 sticky top-6">
                <flux:heading size="lg" class="mb-4">Add Content</flux:heading>
                <div class="grid grid-cols-1 gap-2">
                    <flux:button wire:click="addStep" variant="outline" icon="plus" class="justify-start">
                        Add Normal Step
                    </flux:button>
                    <flux:button wire:click="addRepeat" variant="outline" icon="arrow-path" class="justify-start">
                        Add Repeat Block
                    </flux:button>
                </div>

                <flux:separator class="my-6" />

                <div class="space-y-2">
                    <flux:text size="sm">
                        <strong>Nesting Rule:</strong> Max depth of 2. You cannot nest repeats inside repeats.
                    </flux:text>
                </div>
            </flux:card>
        </div>
    </div>

    <!-- Step Editor Modal -->
    <flux:modal wire:model="showingStepModal" class="md:w-[500px]">
        <div class="space-y-6">
            <flux:heading size="lg">Edit Step</flux:heading>

            <div class="space-y-4">
                @if (($editingStepData['step_kind'] ?? '') === \App\Enums\Workout\StepKind::Repeat->value)
                    <flux:input type="number" wire:model="editingStepData.repeat_count" label="Repeat Count" min="2" />
                    <flux:switch wire:model="editingStepData.skip_last_recovery" label="Skip last recovery" />
                @else
                    <div class="grid grid-cols-2 gap-4">
                        <flux:select wire:model.live="editingStepData.step_kind" label="Step Kind">
                            @foreach(\App\Enums\Workout\StepKind::cases() as $kind)
                                @if($kind !== \App\Enums\Workout\StepKind::Repeat)
                                    <flux:select.option value="{{ $kind->value }}">{{ ucfirst($kind->value) }}</flux:select.option>
                                @endif
                            @endforeach
                        </flux:select>

                        <flux:select wire:model="editingStepData.intensity" label="Intensity">
                            @foreach(\App\Enums\Workout\Intensity::cases() as $intensity)
                                <flux:select.option value="{{ $intensity->value }}">{{ ucfirst($intensity->value) }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>

                    <flux:separator />

                    <div class="space-y-4">
                        <flux:select wire:model.live="editingStepData.duration_type" label="Duration Type">
                            @foreach(\App\Enums\Workout\DurationType::cases() as $type)
                                <flux:select.option value="{{ $type->value }}">{{ str_replace('_', ' ', ucfirst($type->value)) }}</flux:select.option>
                            @endforeach
                        </flux:select>

                        @if(($editingStepData['duration_type'] ?? '') === \App\Enums\Workout\DurationType::Time->value)
                            <div class="flex gap-4 items-end">
                                <flux:input type="number" wire:model="editingStepData.duration_minutes" label="Minutes" min="0" />
                                <flux:input type="number" wire:model="editingStepData.duration_seconds" label="Seconds" min="0" max="59" />
                            </div>
                        @elseif(($editingStepData['duration_type'] ?? '') === \App\Enums\Workout\DurationType::Distance->value)
                            <flux:input type="number" step="0.001" wire:model="editingStepData.duration_km" label="Distance (km)" min="0" />
                        @endif
                    </div>

                    <flux:separator />

                    <div class="space-y-4">
                        <flux:select wire:model.live="editingStepData.target_type" label="Target Type">
                            @foreach(\App\Enums\Workout\TargetType::cases() as $type)
                                <flux:select.option value="{{ $type->value }}">{{ str_replace('_', ' ', ucfirst($type->value)) }}</flux:select.option>
                            @endforeach
                        </flux:select>

                        @if(($editingStepData['target_type'] ?? '') !== \App\Enums\Workout\TargetType::None->value)
                            <flux:select wire:model.live="editingStepData.target_mode" label="Target Mode">
                                <flux:select.option value="{{ \App\Enums\Workout\TargetMode::Zone->value }}">Zone</flux:select.option>
                                <flux:select.option value="{{ \App\Enums\Workout\TargetMode::Range->value }}">Range</flux:select.option>
                            </flux:select>

                            @if(($editingStepData['target_mode'] ?? '') === \App\Enums\Workout\TargetMode::Zone->value)
                                <flux:select wire:model="editingStepData.target_zone" label="Zone">
                                    @foreach(range(1, 5) as $z)
                                        <flux:select.option value="{{ $z }}">Zone {{ $z }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            @elseif(($editingStepData['target_mode'] ?? '') === \App\Enums\Workout\TargetMode::Range->value)
                                @if(($editingStepData['target_type'] ?? '') === \App\Enums\Workout\TargetType::HeartRate->value)
                                    <div class="flex gap-4">
                                        <flux:input type="number" wire:model="editingStepData.target_low" label="Low (bpm)" />
                                        <flux:input type="number" wire:model="editingStepData.target_high" label="High (bpm)" />
                                    </div>
                                @elseif(($editingStepData['target_type'] ?? '') === \App\Enums\Workout\TargetType::Pace->value)
                                    <div class="space-y-2">
                                        <flux:text size="sm">Low Pace (Min/Km)</flux:text>
                                        <div class="flex gap-4">
                                            <flux:input type="number" wire:model="editingStepData.target_low_min" placeholder="Min" />
                                            <flux:input type="number" wire:model="editingStepData.target_low_sec" placeholder="Sec" />
                                        </div>
                                        <flux:text size="sm">High Pace (Min/Km)</flux:text>
                                        <div class="flex gap-4">
                                            <flux:input type="number" wire:model="editingStepData.target_high_min" placeholder="Min" />
                                            <flux:input type="number" wire:model="editingStepData.target_high_sec" placeholder="Sec" />
                                        </div>
                                    </div>
                                @endif
                            @endif
                        @endif
                    </div>
                @endif
            </div>

            <div class="flex gap-2 justify-end">
                <flux:button variant="ghost" wire:click="$set('showingStepModal', false)">Cancel</flux:button>
                <flux:button variant="primary" wire:click="saveStep">Done</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
