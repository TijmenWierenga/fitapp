<div class="p-6 max-w-4xl mx-auto">
    <form wire:submit="save" class="space-y-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <flux:input
                wire:model="name"
                name="name"
                label="Workout Name"
                type="text"
                required
                autofocus
                placeholder="e.g. 5k Interval Session"
            />

            <flux:select wire:model="type" label="Activity Type">
                <flux:select.option value="running">Running</flux:select.option>
                <flux:select.option value="cycling">Cycling</flux:select.option>
                <flux:select.option value="strength_training">Strength Training</flux:select.option>
                <flux:select.option value="swimming">Swimming</flux:select.option>
            </flux:select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
                min="00:00"
                required
            />
        </div>

        <div class="space-y-4">
            <div class="flex items-center justify-between border-b pb-2 dark:border-zinc-700">
                <flux:heading level="2">Workout Steps</flux:heading>
                <div class="flex gap-2">
                    <flux:button wire:click.prevent="addStep" size="sm" icon="plus" variant="outline">Add Step</flux:button>
                    <flux:button wire:click.prevent="addRepeat" size="sm" icon="arrow-path" variant="outline">Add Repeat</flux:button>
                </div>
            </div>

            <div class="space-y-3">
                @foreach ($steps as $index => $step)
                    <div wire:key="step-{{ $index }}" class="p-4 border rounded-xl bg-zinc-50 dark:bg-zinc-900 border-zinc-200 dark:border-zinc-800 relative group">
                        <div class="absolute right-2 top-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <flux:button wire:click.prevent="removeStep({{ $index }})" variant="ghost" icon="trash" size="sm" />
                        </div>

                        @if ($step['type'] === 'repetition')
                            <div class="flex flex-col gap-4">
                                <div class="flex items-center gap-3">
                                     <flux:icon.arrow-path class="text-zinc-400 size-5" />
                                     <div class="flex items-center gap-2 text-sm font-medium">
                                         <span>Repeat</span>
                                         <flux:input wire:model="steps.{{ $index }}.duration_value" size="sm" class="w-16" />
                                         <span>times:</span>
                                     </div>
                                </div>

                                <div class="pl-8 border-l-2 border-zinc-200 dark:border-zinc-700 space-y-3">
                                    @foreach ($step['children'] as $childIndex => $childStep)
                                         <div wire:key="step-{{ $index }}-{{ $childIndex }}" class="flex flex-wrap items-center gap-3">
                                             <flux:select wire:model="steps.{{ $index }}.children.{{ $childIndex }}.intensity" size="sm" class="w-32">
                                                 <flux:select.option value="warmup">Warmup</flux:select.option>
                                                 <flux:select.option value="active">Active</flux:select.option>
                                                 <flux:select.option value="rest">Rest</flux:select.option>
                                                 <flux:select.option value="cooldown">Cooldown</flux:select.option>
                                             </flux:select>

                                             <flux:select wire:model="steps.{{ $index }}.children.{{ $childIndex }}.duration_type" size="sm" class="w-32">
                                                 <flux:select.option value="time">Time (s)</flux:select.option>
                                                 <flux:select.option value="distance">Distance (m)</flux:select.option>
                                                 <flux:select.option value="open">Open</flux:select.option>
                                             </flux:select>

                                             <flux:input wire:model="steps.{{ $index }}.children.{{ $childIndex }}.duration_value" size="sm" class="w-24" placeholder="Value" />

                                             <flux:select wire:model="steps.{{ $index }}.children.{{ $childIndex }}.target_type" size="sm" class="w-32">
                                                 <flux:select.option value="open">Open</flux:select.option>
                                                 <flux:select.option value="pace">Pace</flux:select.option>
                                                 <flux:select.option value="heart_rate">HR</flux:select.option>
                                                 <flux:select.option value="power">Power</flux:select.option>
                                             </flux:select>

                                             <flux:button wire:click.prevent="removeChildStep({{ $index }}, {{ $childIndex }})" variant="ghost" icon="x-mark" size="sm" />
                                         </div>
                                    @endforeach
                                    <flux:button wire:click.prevent="addChildStep({{ $index }})" variant="ghost" size="sm" icon="plus">Add Step to Repeat</flux:button>
                                </div>
                            </div>
                        @else
                            <div class="flex flex-wrap items-center gap-3">
                                 <flux:select wire:model="steps.{{ $index }}.intensity" size="sm" class="w-32">
                                     <flux:select.option value="warmup">Warmup</flux:select.option>
                                     <flux:select.option value="active">Active</flux:select.option>
                                     <flux:select.option value="rest">Rest</flux:select.option>
                                     <flux:select.option value="cooldown">Cooldown</flux:select.option>
                                 </flux:select>

                                 <flux:select wire:model="steps.{{ $index }}.duration_type" size="sm" class="w-32">
                                     <flux:select.option value="time">Time (s)</flux:select.option>
                                     <flux:select.option value="distance">Distance (m)</flux:select.option>
                                     <flux:select.option value="open">Open</flux:select.option>
                                 </flux:select>

                                 <flux:input wire:model="steps.{{ $index }}.duration_value" size="sm" class="w-24" placeholder="Value" />

                                 <flux:select wire:model="steps.{{ $index }}.target_type" size="sm" class="w-32">
                                     <flux:select.option value="open">Open</flux:select.option>
                                     <flux:select.option value="pace">Pace</flux:select.option>
                                     <flux:select.option value="heart_rate">HR</flux:select.option>
                                     <flux:select.option value="power">Power</flux:select.option>
                                 </flux:select>
                            </div>
                        @endif
                    </div>
                @endforeach

                @if (empty($steps))
                    <div class="text-center py-12 border-2 border-dashed rounded-xl border-zinc-200 dark:border-zinc-800">
                        <flux:text variant="subtle" class="mb-4">No steps added to this workout yet.</flux:text>
                        <div class="flex justify-center gap-2">
                            <flux:button wire:click.prevent="addStep" size="sm" variant="outline">Add First Step</flux:button>
                            <flux:button wire:click.prevent="addRepeat" size="sm" variant="outline">Add Repeat Block</flux:button>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="flex items-center justify-end border-t pt-6 dark:border-zinc-700">
            <flux:button variant="primary" type="submit" class="w-full md:w-auto px-8">
                Create Workout
            </flux:button>
        </div>
    </form>
</div>


