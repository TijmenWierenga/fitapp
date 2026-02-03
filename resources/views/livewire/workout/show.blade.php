<div class="max-w-4xl mx-auto p-6">
    {{-- Header with back navigation --}}
    <div class="flex items-center gap-4 mb-6">
        <flux:button href="{{ route('dashboard') }}" variant="ghost" icon="arrow-left" />
        <div class="flex-1">
            <div class="flex items-center gap-2">
                <flux:heading size="xl">{{ $workout->name }}</flux:heading>
                <x-activity-badge :activity="$workout->activity" />
            </div>
        </div>
        <flux:badge color="{{ $this->statusBadge['color'] }}" size="sm">
            {{ $this->statusBadge['text'] }}
        </flux:badge>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main content: 2/3 width --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Workout Details Card --}}
            <flux:card>
                <flux:heading size="lg" class="mb-4">Details</flux:heading>
                <div class="space-y-3">
                    <div class="flex items-center gap-2">
                        <flux:icon.calendar class="size-5 text-zinc-400" />
                        <flux:text>{{ $workout->scheduled_at->format('l, F j, Y') }}</flux:text>
                    </div>
                    <div class="flex items-center gap-2">
                        <flux:icon.clock class="size-5 text-zinc-400" />
                        <flux:text>{{ $workout->scheduled_at->format('g:i A') }}</flux:text>
                    </div>

                    @php
                        $totalDistance = $workout->estimatedTotalDistanceInMeters();
                        $totalDuration = $workout->estimatedTotalDurationInSeconds();
                    @endphp

                    @if($totalDuration > 0)
                        <div class="flex items-center gap-2">
                            <flux:icon.clock class="size-5 text-zinc-400" />
                            <flux:text>Est. {{ \App\Support\Workout\TimeConverter::format($totalDuration) }}</flux:text>
                        </div>
                    @endif

                    @if($totalDistance > 0)
                        <div class="flex items-center gap-2">
                            <flux:icon.bolt class="size-5 text-zinc-400" />
                            <flux:text>Est. {{ \App\Support\Workout\DistanceConverter::format($totalDistance) }}</flux:text>
                        </div>
                    @endif

                    @if($workout->isCompleted())
                        <div class="flex items-center gap-2 text-green-600 dark:text-green-400">
                            <flux:icon.check-circle class="size-5" />
                            <flux:text>Completed {{ $workout->completed_at->diffForHumans() }}</flux:text>
                        </div>
                        @if($workout->rpe)
                            <div class="flex items-center gap-2">
                                <flux:icon.fire class="size-5 text-zinc-400" />
                                <flux:text>RPE: {{ $workout->rpe }}/10 ({{ \App\Models\Workout::getRpeLabel($workout->rpe) }})</flux:text>
                            </div>
                        @endif
                        @if($workout->feeling)
                            @php
                                $feelingEmojis = [
                                    1 => '😞',
                                    2 => '😕',
                                    3 => '😐',
                                    4 => '🙂',
                                    5 => '😊',
                                ];
                                $feelingLabels = [
                                    1 => 'Very Bad',
                                    2 => 'Bad',
                                    3 => 'Okay',
                                    4 => 'Good',
                                    5 => 'Great',
                                ];
                            @endphp
                            <div class="flex items-center gap-2">
                                <span class="text-xl">{{ $feelingEmojis[$workout->feeling] }}</span>
                                <flux:text>Feeling: {{ $feelingLabels[$workout->feeling] }} ({{ $workout->feeling }}/5)</flux:text>
                            </div>
                        @endif
                    @endif
                </div>
            </flux:card>

            {{-- Notes Card --}}
            @if($workout->notes)
                <flux:card>
                    <flux:heading size="lg" class="mb-4">Notes</flux:heading>
                    <div class="prose prose-zinc dark:prose-invert max-w-none text-zinc-600 dark:text-zinc-400">
                        {!! Str::markdown($workout->notes, ['html_input' => 'escape']) !!}
                    </div>
                </flux:card>
            @endif

            {{-- Completion Notes Card --}}
            @if($workout->isCompleted() && $workout->completion_notes)
                <flux:card>
                    <flux:heading size="lg" class="mb-4">Completion Notes</flux:heading>
                    <div class="prose prose-zinc dark:prose-invert max-w-none text-zinc-600 dark:text-zinc-400">
                        {!! Str::markdown($workout->completion_notes, ['html_input' => 'escape']) !!}
                    </div>
                </flux:card>
            @endif

            {{-- Injury Evaluations Card --}}
            @if($workout->isCompleted() && $workout->injuryEvaluations->isNotEmpty())
                <flux:card>
                    <flux:heading size="lg" class="mb-4">Injury Feedback</flux:heading>
                    <div class="space-y-4">
                        @foreach($workout->injuryEvaluations as $evaluation)
                            <div class="p-4 bg-zinc-50 dark:bg-zinc-800/50 rounded-lg">
                                <div class="flex items-center justify-between mb-2">
                                    <flux:badge color="amber" size="sm">{{ $evaluation->injury->body_part->label() }}</flux:badge>
                                    @if($evaluation->discomfort_score)
                                        <span class="text-sm font-medium {{ $evaluation->discomfort_score >= 7 ? 'text-red-600 dark:text-red-400' : ($evaluation->discomfort_score >= 4 ? 'text-amber-600 dark:text-amber-400' : 'text-green-600 dark:text-green-400') }}">
                                            Discomfort: {{ $evaluation->discomfort_score }}/10
                                        </span>
                                    @endif
                                </div>
                                @if($evaluation->notes)
                                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">{{ $evaluation->notes }}</flux:text>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </flux:card>
            @endif

            {{-- Steps Table Card --}}
            @if($workout->rootSteps->isNotEmpty())
                <flux:card>
                    <flux:heading size="lg" class="mb-4">Workout Steps</flux:heading>
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Step</flux:table.column>
                            <flux:table.column>Duration</flux:table.column>
                            <flux:table.column>Target</flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @foreach($workout->rootSteps as $step)
                                @if($step->step_kind === \App\Enums\Workout\StepKind::Repeat)
                                    <x-workout-repeat-header :repeat-count="$step->repeat_count" />
                                    @foreach($step->children as $child)
                                        <x-workout-step-row :step="$child" indented />
                                    @endforeach
                                @else
                                    <x-workout-step-row :step="$step" />
                                @endif
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </flux:card>
            @else
                <flux:card>
                    <x-empty-state icon="document" message="No workout steps defined" />
                </flux:card>
            @endif
        </div>

        {{-- Sidebar: 1/3 width --}}
        <div class="space-y-4">
            {{-- Actions Card --}}
            <flux:card class="sticky top-6">
                <flux:heading size="lg" class="mb-4">Actions</flux:heading>
                <div class="flex flex-col gap-3">
                    @if(!$workout->isCompleted() && ($workout->scheduled_at->isPast() || $workout->scheduled_at->isToday()))
                        <flux:button
                            wire:click="openEvaluationModal"
                            variant="primary"
                            icon="check"
                            class="w-full"
                        >
                            Mark as Completed
                        </flux:button>
                    @endif

                    @if($workout->canBeEdited())
                        <flux:button
                            href="{{ route('workouts.edit', $workout) }}"
                            variant="filled"
                            icon="pencil"
                            class="w-full"
                        >
                            Edit Workout
                        </flux:button>
                    @endif

                    <flux:button
                        wire:click="$dispatch('duplicate-workout', { workoutId: {{ $workout->id }} })"
                        variant="ghost"
                        icon="document-duplicate"
                        class="w-full"
                    >
                        Duplicate
                    </flux:button>

                    @if($workout->canBeDeleted())
                        <flux:button
                            wire:click="deleteWorkout"
                            wire:confirm="Are you sure you want to delete this workout?"
                            variant="danger"
                            icon="trash"
                            class="w-full"
                        >
                            Delete
                        </flux:button>
                    @endif
                </div>
            </flux:card>
        </div>
    </div>

    {{-- Include duplicate modal component --}}
    <livewire:workout.duplicate />

    {{-- Evaluation Modal --}}
    <flux:modal name="evaluation-modal" wire:model.live="showEvaluationModal" @cancel="cancelEvaluation">
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
                    @php
                        $feelingEmojis = [
                            1 => ['emoji' => '😞', 'label' => 'Very Bad'],
                            2 => ['emoji' => '😕', 'label' => 'Bad'],
                            3 => ['emoji' => '😐', 'label' => 'Okay'],
                            4 => ['emoji' => '🙂', 'label' => 'Good'],
                            5 => ['emoji' => '😊', 'label' => 'Great'],
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
</div>
