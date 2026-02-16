<flux:card>
    <flux:heading size="lg" class="mb-4">Next Workout</flux:heading>

    @if($this->nextWorkout)
        <div class="flex flex-col gap-4">
            <div>
                <div class="flex flex-col sm:flex-row sm:items-center gap-2 mb-2">
                    <a href="{{ route('workouts.show', $this->nextWorkout) }}" class="flex-1 min-w-0">
                        <flux:heading size="xl" class="font-bold hover:text-blue-600 dark:hover:text-blue-400 truncate">{{ $this->nextWorkout->name }}</flux:heading>
                    </a>
                    <x-activity-badge :activity="$this->nextWorkout->activity" />
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <flux:text class="text-zinc-500 dark:text-zinc-400 text-sm">
                        {{ $this->nextWorkout->scheduled_at->format('l, M j') }} &middot; {{ $this->nextWorkout->scheduled_at->format('g:i A') }}
                    </flux:text>
                    <x-workout-schedule-badge :scheduled-at="$this->nextWorkout->scheduled_at" />
                </div>
            </div>

            @if($this->nextWorkout->notes)
                <div x-data="{ clamped: false }" x-init="$nextTick(() => { clamped = $refs.notes.scrollHeight > $refs.notes.clientHeight })">
                    <flux:separator />
                    <flux:heading size="sm" class="mb-2 mt-4 text-zinc-500 dark:text-zinc-400">Notes</flux:heading>
                    <div x-ref="notes" class="prose prose-sm prose-zinc dark:prose-invert max-w-none text-zinc-600 dark:text-zinc-400 line-clamp-3">
                        {!! Str::markdown($this->nextWorkout->notes, ['html_input' => 'escape']) !!}
                    </div>
                    <a x-show="clamped" x-cloak href="{{ route('workouts.show', $this->nextWorkout) }}" class="text-xs text-blue-600 dark:text-blue-400 hover:underline mt-1 inline-block">
                        Read more
                    </a>
                </div>
            @endif

            @if($this->nextWorkout->sections->isNotEmpty())
                <div>
                    <flux:separator />
                    <flux:heading size="sm" class="mb-2 mt-4 text-zinc-500 dark:text-zinc-400">Workout Structure</flux:heading>
                    <div class="space-y-4">
                        @foreach($this->nextWorkout->sections as $section)
                            <div>
                                @if($section->name)
                                    <p class="text-xs font-semibold uppercase tracking-wide text-zinc-400 dark:text-zinc-500 mb-1">{{ $section->name }}</p>
                                @endif
                                <div class="space-y-0.5">
                                    @foreach($section->blocks as $block)
                                        @if(!$loop->first)
                                            <flux:separator class="!my-2" />
                                        @endif
                                        @foreach($block->exercises as $exercise)
                                            @php $presentation = $exercise->exerciseable->present(); @endphp
                                            <div class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
                                                <span class="size-1.5 rounded-full {{ $presentation->dotColor }} shrink-0"></span>
                                                @if($exercise->exercise_id)
                                                    <button
                                                        type="button"
                                                        wire:click="$dispatch('show-exercise-detail', { exerciseId: {{ $exercise->exercise_id }} })"
                                                        class="font-medium text-accent hover:underline text-left cursor-pointer"
                                                    >
                                                        {{ $exercise->name }}
                                                    </button>
                                                @else
                                                    <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $exercise->name }}</span>
                                                @endif
                                                @if(!empty($presentation->whatLines))
                                                    <span class="text-zinc-400 dark:text-zinc-500">&middot;</span>
                                                    <span class="truncate">{{ implode(', ', $presentation->whatLines) }}</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <flux:separator />
            <div class="flex flex-col sm:flex-row gap-2">
                <flux:button
                        href="{{ route('workouts.edit', $this->nextWorkout) }}"
                        variant="ghost"
                        class="flex-1 w-full sm:w-auto"
                    >
                        Edit Workout
                    </flux:button>
                <flux:button
                    href="{{ route('workouts.show', $this->nextWorkout) }}"
                    variant="primary"
                    class="flex-1 w-full sm:w-auto"
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

    <livewire:exercise.detail />
</flux:card>
