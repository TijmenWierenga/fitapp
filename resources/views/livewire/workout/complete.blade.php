<div>
    @if($workout)
        <flux:modal name="complete-workout" wire:model.live="showModal" @close="closeModal">
            <form wire:submit="submit" class="space-y-6">
                <div>
                    <flux:heading size="lg">How was your workout?</flux:heading>
                    <flux:text class="mt-1">
                        Rate your effort for <strong>{{ $workout->name }}</strong>.
                    </flux:text>
                </div>

                {{-- RPE Section --}}
                <flux:field>
                    <flux:label>Rate of Perceived Exertion (RPE)</flux:label>
                    <flux:description>How hard did this workout feel?</flux:description>
                    <div class="mt-3">
                        <div class="flex justify-between gap-1">
                            @foreach(range(1, 10) as $value)
                                <button
                                    type="button"
                                    wire:click="$set('rpe', {{ $value }})"
                                    class="flex-1 py-2 text-sm font-medium rounded-md transition-colors {{ $rpe === $value ? 'bg-accent text-accent-foreground' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-600' }}"
                                >
                                    {{ $value }}
                                </button>
                            @endforeach
                        </div>
                        <div class="flex justify-between mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                            <span>Very Easy</span>
                            <span>Easy</span>
                            <span>Moderate</span>
                            <span>Hard</span>
                            <span>Maximum</span>
                        </div>
                        @if($rpe)
                            <flux:text class="mt-2 text-center font-medium">{{ $this->rpeLabel }}</flux:text>
                        @endif
                    </div>
                    <flux:error name="rpe" />
                </flux:field>

                {{-- Feeling Section --}}
                <flux:field>
                    <flux:label>Overall Feeling</flux:label>
                    <flux:description>How did you feel during this workout?</flux:description>
                    <div class="mt-3">
                        @php
                            $feelingEmojis = [
                                1 => ['emoji' => "\u{1F61E}", 'label' => 'Very Bad'],
                                2 => ['emoji' => "\u{1F615}", 'label' => 'Bad'],
                                3 => ['emoji' => "\u{1F610}", 'label' => 'Okay'],
                                4 => ['emoji' => "\u{1F642}", 'label' => 'Good'],
                                5 => ['emoji' => "\u{1F60A}", 'label' => 'Great'],
                            ];
                        @endphp
                        <div class="flex justify-between gap-2">
                            @foreach($feelingEmojis as $value => $data)
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
                            @foreach($feelingEmojis as $data)
                                <span class="flex-1 text-center">{{ $data['label'] }}</span>
                            @endforeach
                        </div>
                    </div>
                    <flux:error name="feeling" />
                </flux:field>

                {{-- Completion Notes Section --}}
                <flux:field>
                    <flux:label>Workout Notes (Optional)</flux:label>
                    <flux:description>Any thoughts about this workout? What went well, challenges, etc.</flux:description>
                    <flux:textarea
                        wire:model="completionNotes"
                        rows="3"
                        placeholder="e.g., Felt strong on the first half, struggled with pacing..."
                    />
                    <flux:error name="completionNotes" />
                </flux:field>

                {{-- Injury Feedback Section --}}
                @if($this->activeInjuries->isNotEmpty())
                    <div class="space-y-4">
                        <div>
                            <flux:label>Injury Feedback (Optional)</flux:label>
                            <flux:description>How did your injuries feel during this workout?</flux:description>
                        </div>

                        @foreach($this->activeInjuries as $injury)
                            <div class="p-4 bg-zinc-50 dark:bg-zinc-800/50 rounded-lg space-y-3">
                                <div class="flex items-center gap-2">
                                    <flux:badge color="amber" size="sm">{{ $injury->body_part->label() }}</flux:badge>
                                    <flux:text class="text-sm text-zinc-500">{{ $injury->injury_type->label() }}</flux:text>
                                </div>

                                {{-- Discomfort Score --}}
                                <div>
                                    <flux:text class="text-sm font-medium mb-2">Discomfort Level</flux:text>
                                    <div class="flex gap-1">
                                        @foreach(range(1, 10) as $score)
                                            <button
                                                type="button"
                                                wire:click="setInjuryDiscomfort({{ $injury->id }}, {{ $score }})"
                                                class="flex-1 py-1.5 text-xs font-medium rounded transition-colors {{ ($injuryEvaluations[$injury->id]['discomfort_score'] ?? null) === $score ? ($score >= 7 ? 'bg-red-500 text-white' : ($score >= 4 ? 'bg-amber-500 text-white' : 'bg-green-500 text-white')) : 'bg-zinc-200 dark:bg-zinc-600 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-300 dark:hover:bg-zinc-500' }}"
                                            >
                                                {{ $score }}
                                            </button>
                                        @endforeach
                                    </div>
                                    <div class="flex justify-between mt-1 text-xs text-zinc-400">
                                        <span>Minimal</span>
                                        <span>Severe</span>
                                    </div>
                                </div>

                                {{-- Notes --}}
                                <flux:textarea
                                    wire:model="injuryEvaluations.{{ $injury->id }}.notes"
                                    rows="2"
                                    placeholder="Any notes about how this injury felt..."
                                />
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="flex gap-2 justify-between">
                    <flux:button type="button" wire:click="closeModal" variant="ghost">
                        Cancel
                    </flux:button>
                    <flux:button type="submit" variant="primary" :disabled="!$rpe || !$feeling" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="submit">Complete Workout</span>
                        <span wire:loading wire:target="submit">Completing...</span>
                    </flux:button>
                </div>
            </form>
        </flux:modal>
    @endif
</div>
