<flux:card class="h-full">
    <flux:heading size="lg" class="mb-4">Next Workout</flux:heading>

    @if($this->nextWorkout)
        <div class="flex flex-col gap-4">
            <div>
                <flux:heading size="xl" class="font-bold">{{ $this->nextWorkout->name }}</flux:heading>
                <flux:text class="text-zinc-500 dark:text-zinc-400 mt-1">
                    {{ $this->nextWorkout->scheduled_at->format('l, F j, Y') }}
                </flux:text>
                <div class="flex items-center gap-3">
                    <flux:text class="text-zinc-500 dark:text-zinc-400">
                        {{ $this->nextWorkout->scheduled_at->format('g:i A') }}
                    </flux:text>
                    @php
                        $totalDistance = $this->nextWorkout->totalDistanceInMeters();
                        $totalDuration = $this->nextWorkout->totalDurationInSeconds();
                    @endphp
                    @if($totalDistance > 0 || $totalDuration > 0)
                        <div class="flex items-center gap-1.5 text-zinc-500 dark:text-zinc-400">
                            <flux:separator vertical class="h-3" />
                            @if($totalDuration > 0)
                                <div class="flex items-center gap-1">
                                    <flux:icon.clock class="size-3.5" />
                                    <span class="text-sm">{{ \App\Support\Workout\TimeConverter::format($totalDuration) }}</span>
                                </div>
                            @endif
                            @if($totalDistance > 0)
                                @if($totalDuration > 0)
                                    <span class="text-zinc-300 dark:text-zinc-700 mx-0.5">•</span>
                                @endif
                                <div class="flex items-center gap-1">
                                    <flux:icon.bolt class="size-3.5" />
                                    <span class="text-sm">{{ \App\Support\Workout\DistanceConverter::format($totalDistance) }}</span>
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
                    <div class="space-y-1">
                        @foreach($this->nextWorkout->rootSteps->take(5) as $step)
                            <div class="flex items-start gap-2 text-sm">
                                <span class="text-zinc-400 mt-1">•</span>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-baseline justify-between gap-2">
                                        <span class="font-medium text-zinc-800 dark:text-zinc-200 truncate">
                                            @if($step->step_kind === \App\Enums\Workout\StepKind::Repeat)
                                                Repeat {{ $step->repeat_count }}x
                                            @else
                                                {{ $step->name ?: ucfirst($step->step_kind->value) }}
                                            @endif
                                        </span>
                                        @if($step->step_kind !== \App\Enums\Workout\StepKind::Repeat)
                                            <div class="flex items-center gap-2 text-xs text-zinc-500 whitespace-nowrap">
                                                <span>{{ \App\Support\Workout\StepSummary::duration($step) }}</span>
                                                @if(\App\Support\Workout\StepSummary::target($step) !== 'No target')
                                                    <span class="text-zinc-300 dark:text-zinc-700">•</span>
                                                    <span>{{ \App\Support\Workout\StepSummary::target($step) }}</span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>

                                    @if($step->step_kind === \App\Enums\Workout\StepKind::Repeat)
                                        <div class="pl-3 mt-1 border-l border-zinc-200 dark:border-zinc-800 space-y-0.5">
                                            @foreach($step->children as $child)
                                                <div class="flex items-baseline justify-between gap-2 text-xs text-zinc-500">
                                                    <span class="truncate">{{ $child->name ?: ucfirst($child->step_kind->value) }}</span>
                                                    <div class="flex items-center gap-1.5 whitespace-nowrap">
                                                        <span>{{ \App\Support\Workout\StepSummary::duration($child) }}</span>
                                                        @if(\App\Support\Workout\StepSummary::target($child) !== 'No target')
                                                            <span class="text-zinc-300 dark:text-zinc-800">•</span>
                                                            <span>{{ \App\Support\Workout\StepSummary::target($child) }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach

                        @if($this->nextWorkout->rootSteps->count() > 5)
                            <div class="text-xs text-zinc-500 pl-4 mt-1">
                                + {{ $this->nextWorkout->rootSteps->count() - 5 }} more steps
                            </div>
                        @endif
                    </div>
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

