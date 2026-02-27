<div class="max-w-6xl mx-auto p-6">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-3">
            <flux:button href="{{ route('injuries.index') }}" variant="ghost" icon="arrow-left" size="sm" wire:navigate />
            <flux:heading size="xl">{{ __('Training Impact') }}</flux:heading>
        </div>
        <flux:button href="{{ route('injuries.index') }}" variant="ghost" icon="list-bullet" wire:navigate>
            {{ __('All Injuries') }}
        </flux:button>
    </div>

    @if($this->activeCount > 0)
        {{-- Alert Banner --}}
        <flux:callout variant="warning" icon="exclamation-triangle" class="mb-8">
            <strong>{{ $this->activeCount }} {{ __('Active Injuries Affecting Your Training') }}</strong>
            <flux:text class="text-sm">
                {{ __('Review the restrictions below and consult your coach for modified training plans.') }}
            </flux:text>
        </flux:callout>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            {{-- Left column (2/3) — Exercise Restrictions --}}
            <div class="lg:col-span-2 space-y-6">
                <flux:card>
                    <div class="space-y-4">
                        <flux:heading size="sm">{{ __('Exercise Restrictions') }}</flux:heading>
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('The following active injuries may affect your training program.') }}
                        </flux:text>

                        <flux:table>
                            <flux:table.columns>
                                <flux:table.column>{{ __('Injury') }}</flux:table.column>
                                <flux:table.column class="hidden sm:table-cell">{{ __('Body Part') }}</flux:table.column>
                                <flux:table.column>{{ __('Severity') }}</flux:table.column>
                                <flux:table.column align="end"></flux:table.column>
                            </flux:table.columns>
                            <flux:table.rows>
                                @foreach($this->activeInjuries as $injury)
                                    <flux:table.row :key="$injury->id">
                                        <flux:table.cell class="font-medium">
                                            {{ $injury->injury_type->label() }}
                                            @if($injury->side && $injury->side !== \App\Enums\Side::NotApplicable)
                                                <flux:text class="text-xs text-zinc-500">({{ $injury->side->label() }})</flux:text>
                                            @endif
                                        </flux:table.cell>
                                        <flux:table.cell class="hidden sm:table-cell">
                                            {{ $injury->body_part->label() }}
                                        </flux:table.cell>
                                        <flux:table.cell>
                                            @if($injury->severity)
                                                <flux:badge :color="$injury->severity->color()" size="sm">
                                                    {{ $injury->severity->label() }}
                                                </flux:badge>
                                            @else
                                                <flux:text class="text-zinc-400">&mdash;</flux:text>
                                            @endif
                                        </flux:table.cell>
                                        <flux:table.cell align="end">
                                            <flux:button
                                                variant="ghost"
                                                size="sm"
                                                icon="eye"
                                                :href="route('injuries.show', $injury)"
                                                wire:navigate
                                            />
                                        </flux:table.cell>
                                    </flux:table.row>
                                @endforeach
                            </flux:table.rows>
                        </flux:table>
                    </div>
                </flux:card>
            </div>

            {{-- Right column (1/3) --}}
            <div class="space-y-6">
                {{-- Impact Summary --}}
                <flux:card>
                    <div class="space-y-4">
                        <flux:heading size="sm">{{ __('Impact Summary') }}</flux:heading>
                        <div class="grid grid-cols-3 gap-4 text-center">
                            <div>
                                <div class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $this->activeCount }}</div>
                                <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Active') }}</flux:text>
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-amber-500">{{ $this->moderateCount }}</div>
                                <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Modify') }}</flux:text>
                            </div>
                            <div>
                                <div class="text-2xl font-bold text-red-500">{{ $this->severeCount }}</div>
                                <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Skip') }}</flux:text>
                            </div>
                        </div>
                    </div>
                </flux:card>

                {{-- Active Injuries --}}
                <flux:card>
                    <div class="space-y-3">
                        <flux:heading size="sm">{{ __('Active Injuries') }}</flux:heading>
                        <div class="space-y-2">
                            @foreach($this->activeInjuries as $injury)
                                <div class="flex items-center justify-between" wire:key="active-{{ $injury->id }}">
                                    <div class="flex items-center gap-2">
                                        <flux:icon.heart class="size-4 text-red-400" />
                                        <flux:text class="text-sm">{{ $injury->body_part->label() }}</flux:text>
                                    </div>
                                    @if($injury->severity)
                                        <flux:badge :color="$injury->severity->color()" size="sm">
                                            {{ $injury->severity->label() }}
                                        </flux:badge>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </flux:card>

                {{-- Coach Recommendations --}}
                <flux:card>
                    <div class="space-y-3">
                        <flux:heading size="sm">{{ __('Coach Recommendations') }}</flux:heading>
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('Get AI-powered training modifications based on your active injuries.') }}
                        </flux:text>
                        <flux:button variant="primary" :href="route('coach')" wire:navigate icon="chat-bubble-left-right">
                            {{ __('Talk to Coach') }}
                        </flux:button>
                    </div>
                </flux:card>
            </div>
        </div>
    @else
        {{-- No active injuries --}}
        <flux:callout variant="info" icon="check-circle">
            {{ __('No active injuries affecting your training. Keep up the great work!') }}
        </flux:callout>
    @endif
</div>
