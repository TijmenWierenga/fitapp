<flux:card class="h-full">
    <flux:heading size="lg" class="mb-4">Next Workout</flux:heading>

    @if($this->nextWorkout)
        <div class="flex flex-col gap-4">
            <div>
                <a href="{{ route('workouts.show', $this->nextWorkout) }}">
                    <flux:heading size="xl" class="font-bold hover:text-blue-600 dark:hover:text-blue-400">{{ $this->nextWorkout->name }}</flux:heading>
                </a>
                <flux:text class="text-zinc-500 dark:text-zinc-400 mt-1">
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
                <flux:text class="text-sm text-zinc-600 dark:text-zinc-300">
                    @if($this->nextWorkout->scheduled_at->isToday())
                        <flux:badge color="green" size="sm">Today</flux:badge>
                    @elseif($this->nextWorkout->scheduled_at->isTomorrow())
                        <flux:badge color="blue" size="sm">Tomorrow</flux:badge>
                    @else
                        <flux:badge color="zinc" size="sm">
                            {{ $this->nextWorkout->scheduled_at->diffForHumans() }}
                        </flux:badge>
                    @endif
                </flux:text>
            </div>

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
                                    <flux:table.row class="bg-zinc-50/50 dark:bg-white/5">
                                        <flux:table.cell colspan="3">
                                            <div class="flex items-center gap-2 text-sm font-bold text-zinc-800 dark:text-white ps-2">
                                                <flux:icon.arrow-path class="size-4" />
                                                Repeat {{ $step->repeat_count }}x
                                            </div>
                                        </flux:table.cell>
                                    </flux:table.row>
                                    @foreach($step->children as $child)
                                        <flux:table.row>
                                            <flux:table.cell class="pl-8!">
                                                <flux:text size="sm" class="truncate">{{ $child->name ?: ucfirst($child->step_kind->value) }}</flux:text>
                                            </flux:table.cell>
                                            <flux:table.cell>
                                                <flux:text size="sm">{{ \App\Support\Workout\StepSummary::duration($child) }}</flux:text>
                                            </flux:table.cell>
                                            <flux:table.cell>
                                                <flux:text size="sm">
                                                    @if(\App\Support\Workout\StepSummary::target($child) !== 'No target')
                                                        {{ \App\Support\Workout\StepSummary::target($child) }}
                                                    @else
                                                        -
                                                    @endif
                                                </flux:text>
                                            </flux:table.cell>
                                        </flux:table.row>
                                    @endforeach
                                @else
                                    <flux:table.row>
                                        <flux:table.cell>
                                            <flux:text size="sm" class="font-medium truncate">{{ $step->name ?: ucfirst($step->step_kind->value) }}</flux:text>
                                        </flux:table.cell>
                                        <flux:table.cell>
                                            <flux:text size="sm">{{ \App\Support\Workout\StepSummary::duration($step) }}</flux:text>
                                        </flux:table.cell>
                                        <flux:table.cell>
                                            <flux:text size="sm">
                                                @if(\App\Support\Workout\StepSummary::target($step) !== 'No target')
                                                    {{ \App\Support\Workout\StepSummary::target($step) }}
                                                @else
                                                    -
                                                @endif
                                            </flux:text>
                                        </flux:table.cell>
                                    </flux:table.row>
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
                    wire:click="markAsCompleted({{ $this->nextWorkout->id }})"
                    variant="primary"
                    class="flex-1"
                >
                    Mark as Completed
                </flux:button>
            </div>
        </div>
    @else
        <div class="flex flex-col items-center justify-center py-8 text-center">
            <flux:icon.calendar class="size-12 text-zinc-400 dark:text-zinc-600 mb-3" />
            <flux:text class="text-zinc-500 dark:text-zinc-400">
                No upcoming workouts scheduled
            </flux:text>
            <flux:button
                href="{{ route('workouts.create') }}"
                variant="primary"
                class="mt-4"
            >
                Schedule Workout
            </flux:button>
        </div>
    @endif
</flux:card>

