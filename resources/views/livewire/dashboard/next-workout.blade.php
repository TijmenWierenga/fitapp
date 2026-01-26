<flux:card class="h-full">
    <flux:heading size="lg" class="mb-4">Next Workout</flux:heading>

    @if($this->nextWorkout)
        <div class="flex flex-col gap-4">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <a href="{{ route('workouts.show', $this->nextWorkout) }}">
                        <flux:heading size="xl" class="font-bold hover:text-blue-600 dark:hover:text-blue-400">{{ $this->nextWorkout->name }}</flux:heading>
                    </a>
                    <x-activity-badge :sport="$this->nextWorkout->sport" />
                </div>
                <flux:text class="text-zinc-500 dark:text-zinc-400">
                    {{ $this->nextWorkout->scheduled_at->format('l, F j, Y') }}
                </flux:text>
                <div class="flex items-center gap-3">
                    <flux:text class="text-zinc-500 dark:text-zinc-400">
                        {{ $this->nextWorkout->scheduled_at->format('g:i A') }}
                    </flux:text>
                    @php
                        $totalDistance = $this->nextWorkout->estimatedTotalDistanceInMeters();
                        $totalDuration = $this->nextWorkout->estimatedTotalDurationInSeconds();
                    @endphp
                    @if($totalDistance > 0 || $totalDuration > 0)
                        <div class="flex items-center gap-1.5 text-zinc-500 dark:text-zinc-400">
                            <flux:separator vertical class="h-3" />
                            @if($totalDuration > 0)
                                <div class="flex items-center gap-1">
                                    <flux:icon.clock class="size-3.5" />
                                    <span class="text-sm">Est. {{ \App\Support\Workout\TimeConverter::format($totalDuration) }}</span>
                                </div>
                            @endif
                            @if($totalDistance > 0)
                                @if($totalDuration > 0)
                                    <span class="text-zinc-300 dark:text-zinc-700 mx-0.5">•</span>
                                @endif
                                <div class="flex items-center gap-1">
                                    <flux:icon.bolt class="size-3.5" />
                                    <span class="text-sm">Est. {{ \App\Support\Workout\DistanceConverter::format($totalDistance) }}</span>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <div class="flex flex-col gap-2">
                <x-workout-schedule-badge :scheduled-at="$this->nextWorkout->scheduled_at" />
            </div>

            @if($this->nextWorkout->notes)
                <flux:card class="bg-zinc-50/50 dark:bg-zinc-800/50 border-zinc-200 dark:border-zinc-700">
                    <flux:heading size="sm" class="mb-2 text-zinc-700 dark:text-zinc-300">Notes</flux:heading>
                    <div class="prose prose-sm prose-zinc dark:prose-invert max-w-none text-zinc-600 dark:text-zinc-400">
                        {!! Str::markdown($this->nextWorkout->notes, ['html_input' => 'escape']) !!}
                    </div>
                </flux:card>
            @endif

            @if($this->nextWorkout->rootSteps->isNotEmpty())
                <div class="space-y-2 mt-4">
                    <flux:heading size="sm" class="text-zinc-500 dark:text-zinc-400">Workout Steps</flux:heading>
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Step</flux:table.column>
                            <flux:table.column>Duration</flux:table.column>
                            <flux:table.column>Target</flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @foreach($this->nextWorkout->rootSteps->take(5) as $step)
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

                    @if($this->nextWorkout->rootSteps->count() > 5)
                        <a href="{{ route('workouts.show', $this->nextWorkout) }}" class="text-xs text-blue-600 dark:text-blue-400 hover:underline mt-2 inline-block">
                            View all {{ $this->nextWorkout->rootSteps->count() }} steps
                        </a>
                    @endif
                </div>
            @endif

            <div class="mt-auto pt-4 flex gap-2">
                @if($this->nextWorkout->canBeEdited())
                    <flux:button
                        href="{{ route('workouts.edit', $this->nextWorkout) }}"
                        variant="ghost"
                        class="flex-1"
                    >
                        Edit Workout
                    </flux:button>
                @endif
                <flux:button
                    href="{{ route('workouts.show', $this->nextWorkout) }}"
                    variant="primary"
                    class="flex-1"
                >
                    View Workout
                </flux:button>
            </div>
        </div>
    @else
        <x-empty-state icon="calendar" message="No upcoming workouts scheduled">
            <flux:button href="{{ route('workouts.create') }}" variant="primary">
                Schedule Workout
            </flux:button>
        </x-empty-state>
    @endif
</flux:card>

