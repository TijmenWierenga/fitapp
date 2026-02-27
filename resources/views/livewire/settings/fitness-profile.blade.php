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

                <flux:field>
                    <flux:switch wire:model="preferGarminExercises" label="{{ __('Prefer Garmin-compatible exercises') }}" />
                    <flux:description>{{ __('When enabled, your AI assistant will prefer exercises that support Garmin device animations and tracking') }}</flux:description>
                </flux:field>

                <div class="flex items-center gap-4">
                    <flux:button variant="primary" type="submit">
                        {{ __('Save Profile') }}
                    </flux:button>

                    <x-action-message class="me-3" on="profile-saved">
                        {{ __('Saved.') }}
                    </x-action-message>
                </div>
            </form>
        </div>
    </x-settings.layout>
</section>
