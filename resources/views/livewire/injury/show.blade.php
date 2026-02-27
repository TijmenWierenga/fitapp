<div class="max-w-6xl mx-auto p-6">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-3">
            <flux:button href="{{ route('injuries.index') }}" variant="ghost" icon="arrow-left" size="sm" wire:navigate />
            <div>
                <div class="flex items-center gap-2">
                    <flux:heading size="xl">
                        @if($injury->side && $injury->side !== \App\Enums\Side::NotApplicable)
                            {{ $injury->side->label() }}
                        @endif
                        {{ $injury->body_part->label() }} &mdash; {{ $injury->injury_type->label() }}
                    </flux:heading>
                    <flux:badge :color="$this->statusColor" size="sm">{{ $this->statusLabel }}</flux:badge>
                </div>
            </div>
        </div>
        <flux:button href="{{ route('injuries.index') }}" variant="primary" icon="pencil" wire:navigate>
            {{ __('Edit Injury') }}
        </flux:button>
    </div>

    {{-- Two-column layout --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Left column (2/3) --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Injury Overview --}}
            <flux:card>
                <div class="space-y-4">
                    <flux:heading size="sm">{{ __('Injury Overview') }}</flux:heading>

                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                        <div>
                            <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Body Part') }}</flux:text>
                            <div class="font-medium text-sm">{{ $injury->body_part->label() }}</div>
                        </div>
                        <div>
                            <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Injury Type') }}</flux:text>
                            <div class="font-medium text-sm">{{ $injury->injury_type->label() }}</div>
                        </div>
                        <div>
                            <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Severity') }}</flux:text>
                            <div class="font-medium text-sm">
                                @if($injury->severity)
                                    <flux:badge :color="$injury->severity->color()" size="sm">{{ $injury->severity->label() }}</flux:badge>
                                @else
                                    &mdash;
                                @endif
                            </div>
                        </div>
                        <div>
                            <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Date Injured') }}</flux:text>
                            <div class="font-medium text-sm">{{ $injury->started_at->format('M j, Y') }}</div>
                        </div>
                    </div>

                    @if($injury->side && $injury->side !== \App\Enums\Side::NotApplicable)
                        <div>
                            <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Side') }}</flux:text>
                            <div class="font-medium text-sm">{{ $injury->side->label() }}</div>
                        </div>
                    @endif

                    @if($injury->how_it_happened)
                        <div>
                            <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('How it happened') }}</flux:text>
                            <div class="text-sm text-zinc-600 dark:text-zinc-300 mt-1">{{ $injury->how_it_happened }}</div>
                        </div>
                    @endif

                    @if($injury->current_symptoms)
                        <div>
                            <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Current Symptoms') }}</flux:text>
                            <div class="text-sm text-zinc-600 dark:text-zinc-300 mt-1">{{ $injury->current_symptoms }}</div>
                        </div>
                    @endif
                </div>
            </flux:card>

            {{-- Recovery Timeline --}}
            <flux:card>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <flux:heading size="sm">{{ __('Recovery Timeline') }}</flux:heading>
                        <flux:button
                            variant="primary"
                            size="sm"
                            icon="plus"
                            :href="route('injuries.reports', $injury)"
                            wire:navigate
                        >
                            {{ __('Add Report') }}
                        </flux:button>
                    </div>

                    @if($injury->injuryReports->isNotEmpty())
                        <div class="space-y-4">
                            @foreach($injury->injuryReports->sortByDesc('reported_at') as $report)
                                <div class="flex gap-3" wire:key="report-{{ $report->id }}">
                                    <div class="flex flex-col items-center">
                                        <div class="size-2 rounded-full bg-accent mt-2"></div>
                                        @if(!$loop->last)
                                            <div class="w-px flex-1 bg-zinc-200 dark:bg-zinc-700 mt-1"></div>
                                        @endif
                                    </div>
                                    <div class="pb-4">
                                        <div class="flex items-center gap-2 mb-1">
                                            <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
                                                {{ $report->reported_at->format('M j, Y') }}
                                            </flux:text>
                                            <flux:badge size="sm" color="zinc">{{ $report->type->label() }}</flux:badge>
                                        </div>
                                        <div class="text-sm text-zinc-600 dark:text-zinc-300 prose prose-sm prose-zinc dark:prose-invert max-w-none">
                                            {!! Str::markdown($report->content, ['html_input' => 'escape']) !!}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <flux:text class="text-zinc-500 dark:text-zinc-400 text-sm">
                            {{ __('No recovery reports yet. Add a report to track your progress.') }}
                        </flux:text>
                    @endif
                </div>
            </flux:card>

            {{-- Notes & Updates --}}
            @if($injury->notes)
                <flux:card>
                    <div class="space-y-3">
                        <flux:heading size="sm">{{ __('Notes & Updates') }}</flux:heading>
                        <div class="text-sm text-zinc-600 dark:text-zinc-300 prose prose-sm prose-zinc dark:prose-invert max-w-none">
                            {!! Str::markdown($injury->notes, ['html_input' => 'escape']) !!}
                        </div>
                    </div>
                </flux:card>
            @endif
        </div>

        {{-- Right column (1/3) --}}
        <div class="space-y-6">
            {{-- Pain Level --}}
            <flux:card>
                <div class="space-y-3">
                    <flux:heading size="sm">{{ __('Pain Level') }}</flux:heading>
                    @if($this->latestPainScore)
                        <div class="text-center">
                            <div class="text-5xl font-bold text-zinc-900 dark:text-white">{{ $this->latestPainScore->pain_score }}</div>
                            <flux:text class="text-zinc-500 dark:text-zinc-400">/10</flux:text>
                            <flux:text class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                                {{ __('Last recorded') }} {{ $this->latestPainScore->created_at->diffForHumans() }}
                            </flux:text>
                        </div>
                    @else
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('No pain scores recorded yet. Complete a workout to log pain levels.') }}
                        </flux:text>
                    @endif
                </div>
            </flux:card>

            {{-- Affected Exercises (placeholder) --}}
            <flux:card>
                <div class="space-y-3">
                    <flux:heading size="sm">{{ __('Affected Exercises') }}</flux:heading>
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('Exercise-injury associations coming soon.') }}
                    </flux:text>
                </div>
            </flux:card>

            {{-- Recommended Modifications (placeholder) --}}
            <flux:card>
                <div class="space-y-3">
                    <flux:heading size="sm">{{ __('Recommended Modifications') }}</flux:heading>
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('Ask your Coach for personalized recommendations.') }}
                    </flux:text>
                    <flux:button variant="primary" size="sm" :href="route('coach')" wire:navigate icon="chat-bubble-left-right">
                        {{ __('Ask Coach') }}
                    </flux:button>
                </div>
            </flux:card>
        </div>
    </div>
</div>
