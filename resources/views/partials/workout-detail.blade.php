@props(['showBackButton' => true, 'showViewFullPage' => false, 'inModal' => false])

@php($headerTag = $inModal ? 'div' : 'flux:card')

{{-- Header --}}
<{{ $headerTag }} @class(['mb-6' => !$inModal])>
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-3 min-w-0">
            @if($showBackButton)
                <flux:button href="{{ route('dashboard') }}" variant="ghost" icon="arrow-left" size="sm" />
            @endif
            <flux:heading size="xl" class="truncate">{{ $workout->name }}</flux:heading>
            <x-activity-badge :activity="$workout->activity" />
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

    <div class="flex flex-col gap-4">
        {{-- Date, time, status --}}
        <div>
            <div class="flex items-center gap-3 text-sm text-zinc-500 dark:text-zinc-400">
                <span class="flex items-center gap-1">
                    <flux:icon.calendar class="size-4" />
                    {{ $workout->scheduled_at->format('l, F j, Y') }}
                </span>
                <span class="flex items-center gap-1">
                    <flux:icon.clock class="size-4" />
                    {{ $workout->scheduled_at->format('g:i A') }}
                </span>
            </div>

            @if($workout->isCompleted())
                <div class="flex items-center gap-2 mt-2 text-sm text-green-600 dark:text-green-400">
                    <flux:icon.check-circle class="size-4" />
                    <span>Completed {{ $workout->completed_at->diffForHumans() }}</span>
                </div>
            @elseif($this->isOverdue)
                <div class="flex items-center gap-2 mt-2 text-sm text-red-600 dark:text-red-400">
                    <flux:icon.exclamation-circle class="size-4" />
                    <span>Overdue</span>
                </div>
            @elseif($workout->scheduled_at->isToday())
                <div class="flex items-center gap-2 mt-2 text-sm text-green-600 dark:text-green-400">
                    <flux:icon.calendar class="size-4" />
                    <span>Today</span>
                </div>
            @elseif($workout->scheduled_at->isTomorrow())
                <div class="flex items-center gap-2 mt-2 text-sm text-blue-600 dark:text-blue-400">
                    <flux:icon.calendar class="size-4" />
                    <span>Tomorrow</span>
                </div>
            @endif
        </div>

        {{-- Evaluation data for completed workouts --}}
        @if($workout->isCompleted() && ($workout->rpe || $workout->feeling))
            <div class="flex items-center gap-4 flex-wrap text-sm text-zinc-600 dark:text-zinc-400">
                @if($workout->rpe)
                    <span class="flex items-center gap-1">
                        <flux:icon.fire class="size-4 text-zinc-400" />
                        RPE: {{ $workout->rpe }}/10 ({{ \App\Models\Workout::getRpeLabel($workout->rpe) }})
                    </span>
                @endif
                @if($workout->feeling)
                    @php($feeling = \App\Models\Workout::feelingScale()[$workout->feeling])
                    <span class="flex items-center gap-1">
                        <span>{{ $feeling['emoji'] }}</span>
                        Feeling: {{ $feeling['label'] }} ({{ $workout->feeling }}/5)
                    </span>
                @endif
            </div>
        @endif

        {{-- Mark as Completed CTA --}}
        @if(!$workout->isCompleted() && ($workout->scheduled_at->isPast() || $workout->scheduled_at->isToday()))
            <flux:button
                wire:click="openEvaluationModal"
                variant="primary"
                icon="check"
            >
                Mark as Completed
            </flux:button>
        @endif

        {{-- Notes --}}
        @if($workout->notes)
            <div>
                <flux:separator />
                <div class="prose prose-zinc dark:prose-invert max-w-none text-zinc-600 dark:text-zinc-400 mt-4">
                    {!! Str::markdown($workout->notes, ['html_input' => 'escape']) !!}
                </div>
            </div>
        @endif
    </div>
</{{ $headerTag }}>

{{-- Workout Structure --}}
@if($workout->sections->isNotEmpty())
    <x-workout.structure :sections="$workout->sections" />
@endif
