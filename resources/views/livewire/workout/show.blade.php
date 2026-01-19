<div class="max-w-4xl mx-auto p-6">
    {{-- Header with back navigation --}}
    <div class="flex items-center gap-4 mb-6">
        <flux:button href="{{ route('dashboard') }}" variant="ghost" icon="arrow-left" />
        <div class="flex-1">
            <div class="flex items-center gap-2">
                <flux:heading size="xl">{{ $workout->name }}</flux:heading>
                <x-activity-badge :sport="$workout->sport" />
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
                    @endif
                </div>
            </flux:card>

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
                            wire:click="markAsCompleted"
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
</div>
