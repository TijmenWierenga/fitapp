<div class="max-w-2xl mx-auto py-8">
    <div class="mb-8">
        <flux:heading size="xl">{{ __('Set Up Your Fitness Profile') }}</flux:heading>
        <flux:subheading>{{ __('Help us personalize your training plans') }}</flux:subheading>
    </div>

    {{-- Step indicator --}}
    <div class="flex items-center gap-2 mb-8">
        @foreach ([1 => 'About You', 2 => 'Your Goals', 3 => 'Your Setup'] as $step => $label)
            <div class="flex items-center gap-2 {{ $step < 3 ? 'flex-1' : '' }}">
                <div @class([
                    'flex items-center justify-center w-8 h-8 rounded-full text-sm font-medium shrink-0',
                    'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' => $currentStep >= $step,
                    'bg-zinc-200 text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400' => $currentStep < $step,
                ])>
                    {{ $step }}
                </div>
                <span @class([
                    'text-sm font-medium',
                    'text-zinc-900 dark:text-white' => $currentStep >= $step,
                    'text-zinc-400 dark:text-zinc-500' => $currentStep < $step,
                ])>
                    {{ __($label) }}
                </span>
                @if ($step < 3)
                    <div @class([
                        'flex-1 h-px',
                        'bg-zinc-900 dark:bg-white' => $currentStep > $step,
                        'bg-zinc-200 dark:bg-zinc-700' => $currentStep <= $step,
                    ])></div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Step 1: About You --}}
    @if ($currentStep === 1)
        <div class="space-y-6">
            <flux:field>
                <flux:label>{{ __('Experience Level') }}</flux:label>
                <flux:select wire:model="experienceLevel" required>
                    <flux:select.option value="">{{ __('Select your experience level...') }}</flux:select.option>
                    @foreach ($this->experienceLevels as $level)
                        <flux:select.option :value="$level->value">
                            {{ $level->label() }} — {{ $level->description() }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="experienceLevel"/>
            </flux:field>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <flux:field>
                    <flux:label>{{ __('Date of Birth') }}</flux:label>
                    <flux:input type="date" wire:model="dateOfBirth" />
                    <flux:description>{{ __('Used for age-based training calculations') }}</flux:description>
                    <flux:error name="dateOfBirth"/>
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Biological Sex') }}</flux:label>
                    <flux:select wire:model="biologicalSex">
                        <flux:select.option value="">{{ __('Select...') }}</flux:select.option>
                        @foreach ($this->biologicalSexOptions as $sex)
                            <flux:select.option :value="$sex->value">
                                {{ $sex->label() }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:description>{{ __('Used for HR zones and strength baselines') }}</flux:description>
                    <flux:error name="biologicalSex"/>
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Body Weight') }}</flux:label>
                    <flux:input.group>
                        <flux:input type="number" wire:model="bodyWeightKg" step="0.1" min="20" max="300" />
                        <flux:input.group.suffix>kg</flux:input.group.suffix>
                    </flux:input.group>
                    <flux:error name="bodyWeightKg"/>
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Height') }}</flux:label>
                    <flux:input.group>
                        <flux:input type="number" wire:model="heightCm" min="100" max="250" />
                        <flux:input.group.suffix>cm</flux:input.group.suffix>
                    </flux:input.group>
                    <flux:error name="heightCm"/>
                </flux:field>
            </div>

            <div class="flex justify-end">
                <flux:button variant="primary" wire:click="nextStep">
                    {{ __('Continue') }}
                </flux:button>
            </div>
        </div>
    @endif

    {{-- Step 2: Your Goals --}}
    @if ($currentStep === 2)
        <div class="space-y-6">
            <flux:field>
                <flux:label>{{ __('Primary Fitness Goal') }}</flux:label>
                <flux:select wire:model="primaryGoal" required>
                    <flux:select.option value="">{{ __('Select a goal...') }}</flux:select.option>
                    @foreach ($this->fitnessGoals as $goal)
                        <flux:select.option :value="$goal->value">
                            {{ $goal->label() }} — {{ $goal->description() }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
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
                    <flux:error name="minutesPerSession"/>
                </flux:field>
            </div>

            <div class="flex justify-between">
                <flux:button variant="ghost" wire:click="previousStep">
                    {{ __('Back') }}
                </flux:button>
                <flux:button variant="primary" wire:click="nextStep">
                    {{ __('Continue') }}
                </flux:button>
            </div>
        </div>
    @endif

    {{-- Step 3: Your Setup --}}
    @if ($currentStep === 3)
        <div class="space-y-6">
            <flux:field>
                <flux:switch wire:model="hasGymAccess" label="{{ __('I have access to a gym') }}" />
                <flux:description>{{ __('Standard gym equipment: barbells, dumbbells, cables, machines, etc.') }}</flux:description>
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Equipment Available at Home') }}</flux:label>
                <flux:description class="mb-2">{{ __('Select equipment you have at home for home workouts') }}</flux:description>
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                    @foreach ($this->equipmentOptions as $equipment)
                        <flux:checkbox
                            wire:model="homeEquipment"
                            :value="$equipment->value"
                            :label="$equipment->label()"
                        />
                    @endforeach
                </div>
                <flux:error name="homeEquipment"/>
            </flux:field>

            <flux:field>
                <flux:switch wire:model="preferGarminExercises" label="{{ __('Prefer Garmin-compatible exercises') }}" />
                <flux:description>{{ __('When enabled, your AI assistant will prefer exercises that support Garmin device animations and tracking') }}</flux:description>
            </flux:field>

            <div class="flex justify-between">
                <flux:button variant="ghost" wire:click="previousStep">
                    {{ __('Back') }}
                </flux:button>
                <flux:button variant="primary" wire:click="save">
                    {{ __('Complete Setup') }}
                </flux:button>
            </div>
        </div>
    @endif
</div>
