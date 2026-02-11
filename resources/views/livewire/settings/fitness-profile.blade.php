<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout
        :heading="__('Fitness Profile')"
        :subheading="__('Set your fitness goals and availability to help personalize your training plans')"
    >
        <div class="flex flex-col w-full mx-auto space-y-8 text-sm" wire:cloak>
            {{-- Fitness Profile Form --}}
            <form wire:submit="saveProfile" class="space-y-6">
                <flux:field>
                    <flux:label>{{ __('Primary Fitness Goal') }}</flux:label>
                    <flux:select wire:model="primaryGoal" required>
                        <flux:select.option value="">{{ __('Select a goal...') }}</flux:select.option>
                        @foreach ($this->fitnessGoals as $goal)
                            <flux:select.option :value="$goal->value">
                                {{ $goal->label() }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:description>{{ __('Your main focus for training') }}</flux:description>
                    <flux:error name="primaryGoal"/>
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Goal Details') }}</flux:label>
                    <flux:textarea
                        wire:model="goalDetails"
                        rows="3"
                        :placeholder="__('e.g., Run a sub-4hr marathon by October, lose 10kg by summer...')"
                    />
                    <flux:description>{{ __('Optional: Describe your specific goals in more detail') }}</flux:description>
                    <flux:error name="goalDetails"/>
                </flux:field>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <flux:field>
                        <flux:label>{{ __('Available Days Per Week') }}</flux:label>
                        <flux:select wire:model="availableDaysPerWeek" required>
                            @for ($i = 1; $i <= 7; $i++)
                                <flux:select.option :value="$i">
                                    {{ $i }} {{ $i === 1 ? __('day') : __('days') }}
                                </flux:select.option>
                            @endfor
                        </flux:select>
                        <flux:description>{{ __('How many days can you train?') }}</flux:description>
                        <flux:error name="availableDaysPerWeek"/>
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Minutes Per Session') }}</flux:label>
                        <flux:select wire:model="minutesPerSession" required>
                            @foreach ([15, 30, 45, 60, 75, 90, 120, 150, 180] as $minutes)
                                <flux:select.option :value="$minutes">
                                    {{ $minutes }} {{ __('minutes') }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:description>{{ __('Typical workout duration') }}</flux:description>
                        <flux:error name="minutesPerSession"/>
                    </flux:field>
                </div>

                <div class="flex items-center gap-4">
                    <flux:button variant="primary" type="submit">
                        {{ __('Save Profile') }}
                    </flux:button>

                    <x-action-message class="me-3" on="profile-saved">
                        {{ __('Saved.') }}
                    </x-action-message>
                </div>
            </form>

            {{-- Injuries Section --}}
            <flux:separator />

            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:heading size="sm">{{ __('Injuries & Limitations') }}</flux:heading>
                        <flux:subheading>{{ __('Track injuries to receive appropriate workout modifications') }}</flux:subheading>
                    </div>

                    <flux:button
                        variant="primary"
                        icon="plus"
                        icon:variant="outline"
                        wire:click="openInjuryModal"
                    >
                        {{ __('Add Injury') }}
                    </flux:button>
                </div>

                @if($this->injuries->isNotEmpty())
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>{{ __('Body Part') }}</flux:table.column>
                            <flux:table.column class="hidden sm:table-cell">{{ __('Type') }}</flux:table.column>
                            <flux:table.column class="hidden md:table-cell">{{ __('Status') }}</flux:table.column>
                            <flux:table.column align="end">{{ __('Actions') }}</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach($this->injuries as $injury)
                                <flux:table.row :key="$injury->id">
                                    <flux:table.cell class="font-medium">
                                        {{ $injury->body_part->label() }}
                                        @if($injury->notes)
                                            <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
                                                {{ Str::limit($injury->notes, 30) }}
                                            </flux:text>
                                        @endif
                                    </flux:table.cell>
                                    <flux:table.cell class="hidden sm:table-cell">
                                        {{ $injury->injury_type->label() }}
                                    </flux:table.cell>
                                    <flux:table.cell class="hidden md:table-cell">
                                        @if($injury->is_active)
                                            <flux:badge color="red">{{ __('Active') }}</flux:badge>
                                        @else
                                            <flux:badge color="green">{{ __('Resolved') }}</flux:badge>
                                        @endif
                                    </flux:table.cell>
                                    <flux:table.cell align="end">
                                        <div class="flex items-center justify-end gap-2">
                                            <flux:button
                                                variant="ghost"
                                                size="sm"
                                                icon="document-text"
                                                :href="route('injuries.reports', $injury)"
                                            />
                                            <flux:button
                                                variant="ghost"
                                                size="sm"
                                                icon="pencil"
                                                wire:click="openInjuryModal({{ $injury->id }})"
                                            />
                                            <flux:button
                                                variant="ghost"
                                                size="sm"
                                                icon="trash"
                                                wire:click="deleteInjury({{ $injury->id }})"
                                                wire:confirm="{{ __('Are you sure you want to delete this injury?') }}"
                                            />
                                        </div>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                @else
                    <flux:callout variant="info" icon="information-circle">
                        {{ __("No injuries recorded. Add any current or past injuries to help customize your training.") }}
                    </flux:callout>
                @endif
            </div>
        </div>
    </x-settings.layout>

    {{-- Injury Modal --}}
    <flux:modal
        name="injury-modal"
        wire:model="showInjuryModal"
        class="max-w-md"
    >
        <form wire:submit="saveInjury" class="space-y-6">
            <x-modal-icon
                icon="heart"
                :heading="$editingInjuryId ? __('Edit Injury') : __('Add Injury')"
            />

            <flux:field>
                <flux:label>{{ __('Injury Type') }}</flux:label>
                <flux:select wire:model="injuryType" required>
                    <flux:select.option value="">{{ __('Select type...') }}</flux:select.option>
                    @foreach ($this->injuryTypes as $type)
                        <flux:select.option :value="$type->value">
                            {{ $type->label() }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="injuryType"/>
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Body Part') }}</flux:label>
                <flux:select wire:model="bodyPart" required searchable>
                    <flux:select.option value="">{{ __('Select body part...') }}</flux:select.option>
                    @foreach ($this->bodyPartsGrouped as $region => $parts)
                        @foreach ($parts as $part)
                            <flux:select.option :value="$part->value">
                                {{ $region }} - {{ $part->label() }}
                            </flux:select.option>
                        @endforeach
                    @endforeach
                </flux:select>
                <flux:error name="bodyPart"/>
            </flux:field>

            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>{{ __('Started') }}</flux:label>
                    <flux:input type="date" wire:model="startedAt" required />
                    <flux:error name="startedAt"/>
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Ended') }}</flux:label>
                    <flux:input type="date" wire:model="endedAt" />
                    <flux:description>{{ __('Leave blank if ongoing') }}</flux:description>
                    <flux:error name="endedAt"/>
                </flux:field>
            </div>

            <flux:field>
                <flux:label>{{ __('Notes') }}</flux:label>
                <flux:textarea
                    wire:model="injuryNotes"
                    rows="2"
                    :placeholder="__('Any additional details about the injury...')"
                />
                <flux:error name="injuryNotes"/>
            </flux:field>

            <div class="flex gap-3">
                <flux:button type="button" variant="outline" class="flex-1" wire:click="closeInjuryModal">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button type="submit" variant="primary" class="flex-1">
                    {{ $editingInjuryId ? __('Update') : __('Add') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</section>
