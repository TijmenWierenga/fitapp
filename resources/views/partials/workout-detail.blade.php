@use('App\Domain\Workload\Calculators\DurationEstimator')
@use('App\Domain\Workload\PlannedBlockMapper')

@props(['showBackButton' => true, 'showViewFullPage' => false, 'inModal' => false])

@php
    $estimatedSeconds = (new DurationEstimator)->estimate(PlannedBlockMapper::fromWorkout($workout));
    $estimatedMinutes = $estimatedSeconds ? (int) round($estimatedSeconds / 60) : null;
@endphp

<div>
    {{-- Header --}}
    <div class="flex items-center justify-between px-6 py-5 border-b border-zinc-200 dark:border-zinc-700">
        <div class="flex items-center gap-2.5 min-w-0">
            @if($showBackButton)
                <flux:button href="{{ route('dashboard') }}" variant="ghost" icon="arrow-left" size="sm" />
            @endif
            <flux:icon.{{ $workout->activity->icon() }} class="size-[18px] text-accent shrink-0" />
            <span class="text-base font-semibold text-zinc-900 dark:text-white truncate">{{ $workout->name }}</span>
        </div>
        <div class="flex items-center gap-1">
            <flux:dropdown position="bottom" align="end">
                <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />
                <flux:menu>
                    @if($showViewFullPage)
                        <flux:menu.item icon="arrow-top-right-on-square" :href="route('workouts.show', $workout)">View Full Page</flux:menu.item>
                    @endif
                    <flux:menu.item icon="pencil" :href="route('workouts.edit', $workout)">Edit Workout</flux:menu.item>
                    <flux:menu.item icon="arrow-down-tray" :href="route('workouts.export-fit', $workout)">Export to Garmin</flux:menu.item>
                    <flux:menu.item icon="document-duplicate" wire:click="$dispatch('duplicate-workout', { workoutId: {{ $workout->id }} })">Duplicate</flux:menu.item>
                    @if($workout->canBeDeleted())
                        <flux:menu.separator />
                        <flux:menu.item variant="danger" icon="trash" wire:click="deleteWorkout" wire:confirm="Are you sure you want to delete this workout?">Delete</flux:menu.item>
                    @endif
                </flux:menu>
            </flux:dropdown>
            @if($inModal)
                <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="closeModal" />
            @endif
        </div>
    </div>

    {{-- Meta pills row --}}
    <div class="flex items-center gap-2.5 flex-wrap px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
        {{-- Date pill --}}
        <div class="rounded-xl bg-zinc-200 dark:bg-zinc-700 px-3 py-1.5 flex items-center gap-1.5 text-xs text-zinc-600 dark:text-zinc-400">
            <flux:icon.calendar class="size-3.5 text-zinc-400 dark:text-zinc-500" />
            <span>{{ $workout->scheduled_at->format('D, M j') }} · {{ $workout->scheduled_at->format('H:i') }}</span>
        </div>

        {{-- Duration pill --}}
        @if($estimatedMinutes)
            <div class="rounded-xl bg-zinc-200 dark:bg-zinc-700 px-3 py-1.5 flex items-center gap-1.5 text-xs text-zinc-600 dark:text-zinc-400">
                <flux:icon.clock class="size-3.5 text-zinc-400 dark:text-zinc-500" />
                <span>~{{ $estimatedMinutes }} min</span>
            </div>
        @endif

        {{-- Status pill --}}
        @if($workout->isCompleted())
            <div class="rounded-xl bg-accent px-3 py-1.5 flex items-center gap-1.5 text-xs font-semibold text-zinc-900">
                <flux:icon.check-circle class="size-3.5" />
                <span>Completed</span>
            </div>
        @elseif($this->isOverdue)
            <div class="rounded-xl bg-red-100 dark:bg-red-900/30 px-3 py-1.5 flex items-center gap-1.5 text-xs font-semibold text-red-700 dark:text-red-400">
                <flux:icon.exclamation-circle class="size-3.5" />
                <span>Overdue</span>
            </div>
        @elseif($workout->scheduled_at->isToday())
            <div class="rounded-xl bg-green-100 dark:bg-green-900/30 px-3 py-1.5 flex items-center gap-1.5 text-xs font-semibold text-green-700 dark:text-green-400">
                <flux:icon.calendar class="size-3.5" />
                <span>Today</span>
            </div>
        @elseif($workout->scheduled_at->isTomorrow())
            <div class="rounded-xl bg-blue-100 dark:bg-blue-900/30 px-3 py-1.5 flex items-center gap-1.5 text-xs font-semibold text-blue-700 dark:text-blue-400">
                <flux:icon.calendar class="size-3.5" />
                <span>Tomorrow</span>
            </div>
        @endif
    </div>

    {{-- Evaluation data for completed workouts --}}
    @if($workout->isCompleted() && ($workout->rpe || $workout->feeling))
        <div class="flex items-center gap-4 flex-wrap px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 text-sm text-zinc-600 dark:text-zinc-400">
            @if($workout->rpe)
                <span class="flex items-center gap-1.5">
                    <flux:icon.fire class="size-4 text-zinc-400" />
                    RPE: {{ $workout->rpe }}/10 ({{ \App\Models\Workout::getRpeLabel($workout->rpe) }})
                </span>
            @endif
            @if($workout->feeling)
                @php($feeling = \App\Models\Workout::feelingScale()[$workout->feeling])
                <span class="flex items-center gap-1.5">
                    <span>{{ $feeling['emoji'] }}</span>
                    Feeling: {{ $feeling['label'] }} ({{ $workout->feeling }}/5)
                </span>
            @endif
        </div>
    @endif

    {{-- Pain scores for completed workouts --}}
    @if($workout->isCompleted() && $workout->painScores->isNotEmpty())
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 space-y-2">
            <div class="flex items-center gap-1.5">
                <flux:icon.heart class="size-3.5 text-zinc-400 dark:text-zinc-500" />
                <span class="text-[11px] font-medium text-zinc-400 dark:text-zinc-500">Pain Assessment</span>
            </div>
            <div class="flex items-center gap-4 flex-wrap text-sm text-zinc-600 dark:text-zinc-400">
                @foreach($workout->painScores as $painScore)
                    <span>
                        {{ $painScore->injury->body_part->label() }}: {{ $painScore->pain_score }}/10 ({{ \App\Models\WorkoutPainScore::getPainLabel($painScore->pain_score) }})
                    </span>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Mark as Completed CTA --}}
    @if(!$workout->isCompleted() && ($workout->scheduled_at->isPast() || $workout->scheduled_at->isToday()))
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
            <flux:button
                wire:click="openEvaluationModal"
                variant="primary"
                icon="check"
            >
                Mark as Completed
            </flux:button>
        </div>
    @endif

    {{-- Workout Notes --}}
    @if($workout->notes)
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 space-y-2">
            <div class="flex items-center gap-1.5">
                <flux:icon.document-text class="size-3.5 text-zinc-400 dark:text-zinc-500" />
                <span class="text-[11px] font-medium text-zinc-400 dark:text-zinc-500">Workout Notes</span>
            </div>
            <div class="text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed">
                {!! Str::markdown($workout->notes, ['html_input' => 'escape']) !!}
            </div>
        </div>
    @endif

    {{-- Sections --}}
    @if($workout->sections->isNotEmpty())
        {{-- Sections label --}}
        <div class="px-6 py-3">
            <span class="text-[10px] font-medium tracking-[2px] text-zinc-400 dark:text-zinc-500 uppercase">Sections</span>
        </div>

        {{-- Sections content --}}
        <div class="px-6 pb-6 space-y-4">
            <x-workout.structure :sections="$workout->sections" />
        </div>
    @endif
</div>
