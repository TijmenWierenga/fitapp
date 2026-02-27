@props(['modalName' => 'evaluation-modal'])

<flux:modal name="{{ $modalName }}" wire:model.live="showEvaluationModal" @cancel="cancelEvaluation" class="max-w-2xl">
    <form wire:submit="submitEvaluation" class="space-y-6">
        <div>
            <flux:heading size="lg">How was your workout?</flux:heading>
            <flux:text class="mt-1">Rate your effort and how you felt during this session.</flux:text>
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

        {{-- Pain Check Section (only if user has active injuries) --}}
        @if(isset($activeInjuries) && $activeInjuries && $activeInjuries->isNotEmpty())
            <flux:separator />

            <flux:field>
                <flux:label>Pain Check</flux:label>
                <flux:description>Optionally rate any pain from your active injuries during this workout.</flux:description>

                <div class="mt-3 space-y-4">
                    @foreach($activeInjuries as $injury)
                        <div wire:key="pain-{{ $injury->id }}">
                            <div class="flex items-center gap-2 mb-2">
                                <flux:text class="text-sm font-medium">{{ $injury->body_part->label() }}</flux:text>
                                @if($injury->severity)
                                    <flux:badge :color="$injury->severity->color()" size="sm">{{ $injury->severity->label() }}</flux:badge>
                                @endif
                                @if($injury->side && $injury->side !== \App\Enums\Side::NotApplicable)
                                    <flux:text class="text-xs text-zinc-500">({{ $injury->side->label() }})</flux:text>
                                @endif
                            </div>
                            <div class="flex justify-between gap-1">
                                @foreach(range(1, 10) as $value)
                                    <button
                                        type="button"
                                        wire:click="$set('painScores.{{ $injury->id }}', {{ $painScores[$injury->id] === $value ? 'null' : $value }})"
                                        class="flex-1 py-1.5 text-xs font-medium rounded-md transition-colors {{ ($painScores[$injury->id] ?? null) === $value ? 'bg-accent text-accent-foreground' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-600' }}"
                                    >
                                        {{ $value }}
                                    </button>
                                @endforeach
                            </div>
                            <div class="flex justify-between mt-1 text-[10px] text-zinc-500 dark:text-zinc-400">
                                <span>No Pain</span>
                                <span>Worst Pain</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </flux:field>
        @endif

        <div class="flex gap-2 justify-between">
            <flux:button type="button" wire:click="cancelEvaluation" variant="ghost">
                Cancel
            </flux:button>
            <flux:button type="submit" variant="primary" :disabled="!$rpe || !$feeling" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="submitEvaluation">Complete Workout</span>
                <span wire:loading wire:target="submitEvaluation">Completing...</span>
            </flux:button>
        </div>
    </form>
</flux:modal>
