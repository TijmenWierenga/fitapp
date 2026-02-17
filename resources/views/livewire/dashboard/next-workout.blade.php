@use('App\Enums\Workout\BlockType')
@use('App\Support\Workout\WorkoutDisplayFormatter as Format')

<flux:card>
    <div class="flex items-center justify-between mb-4">
        <flux:heading size="lg">Next Workout</flux:heading>

        @if($this->nextWorkout)
            <flux:dropdown position="bottom" align="end">
                <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />
                <flux:menu>
                    <flux:menu.item icon="eye" :href="route('workouts.show', $this->nextWorkout)">View</flux:menu.item>
                    <flux:menu.item icon="pencil" :href="route('workouts.edit', $this->nextWorkout)">Edit</flux:menu.item>
                    <flux:menu.item icon="document-duplicate" wire:click="$dispatch('duplicate-workout', { workoutId: {{ $this->nextWorkout->id }} })">Duplicate</flux:menu.item>
                    <flux:menu.separator />
                    <flux:menu.item variant="danger" icon="trash" wire:click="deleteWorkout({{ $this->nextWorkout->id }})" wire:confirm="Are you sure you want to delete this workout?">Delete</flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        @endif
    </div>

    @if($this->nextWorkout)
        <div class="flex flex-col gap-4">
            <div>
                <a href="{{ route('workouts.show', $this->nextWorkout) }}" class="block mb-2">
                    <flux:heading size="xl" class="font-bold hover:text-accent truncate">{{ $this->nextWorkout->name }}</flux:heading>
                </a>
                <div class="flex items-center gap-2 flex-wrap mb-2">
                    <x-activity-badge :activity="$this->nextWorkout->activity" />
                </div>
                <div class="flex items-center gap-3 text-sm text-zinc-500 dark:text-zinc-400">
                    <span class="flex items-center gap-1">
                        <flux:icon.calendar class="size-4" />
                        {{ $this->nextWorkout->scheduled_at->format('l, M j') }}
                    </span>
                    <span class="flex items-center gap-1">
                        <flux:icon.clock class="size-4" />
                        {{ $this->nextWorkout->scheduled_at->format('g:i A') }}
                    </span>
                </div>
            </div>

            @if($this->nextWorkout->notes)
                <div x-data="{ clamped: false }" x-init="$nextTick(() => { clamped = $refs.notes.scrollHeight > $refs.notes.clientHeight })">
                    <flux:separator />
                    <div x-ref="notes" class="prose prose-sm prose-zinc dark:prose-invert max-w-none text-zinc-600 dark:text-zinc-400 line-clamp-2 mt-4">
                        {!! Str::markdown($this->nextWorkout->notes, ['html_input' => 'escape']) !!}
                    </div>
                    <a x-show="clamped" x-cloak href="{{ route('workouts.show', $this->nextWorkout) }}" class="text-xs text-accent hover:underline mt-1 inline-block">
                        Read more
                    </a>
                </div>
            @endif

            @if($this->nextWorkout->sections->isNotEmpty())
                <div>
                    <flux:separator />
                    <div class="mt-4 space-y-4">
                        @foreach($this->nextWorkout->sections as $section)
                            <div>
                                @if($section->name)
                                    <p class="text-xs font-semibold uppercase tracking-wide text-zinc-400 dark:text-zinc-500 mb-2">{{ $section->name }}</p>
                                @endif
                                <div class="space-y-3">
                                    @foreach($section->blocks as $block)
                                        @php
                                            $borderClass = match($block->block_type) {
                                                BlockType::StraightSets => 'border-zinc-400 dark:border-zinc-500',
                                                BlockType::Circuit => 'border-amber-400 dark:border-amber-500',
                                                BlockType::Superset => 'border-violet-400 dark:border-violet-500',
                                                BlockType::Interval => 'border-blue-400 dark:border-blue-500',
                                                BlockType::Amrap => 'border-red-400 dark:border-red-500',
                                                BlockType::ForTime => 'border-orange-400 dark:border-orange-500',
                                                BlockType::Emom => 'border-cyan-400 dark:border-cyan-500',
                                                BlockType::DistanceDuration => 'border-green-400 dark:border-green-500',
                                                BlockType::Rest => 'border-zinc-300 dark:border-zinc-600',
                                            };
                                            $isRest = $block->block_type === BlockType::Rest;

                                            $meta = collect([
                                                $block->rounds ? "{$block->rounds} rounds" : null,
                                                Format::intervals($block->work_interval, $block->rest_interval),
                                                $block->time_cap ? 'cap: ' . Format::duration($block->time_cap) : null,
                                            ])->filter()->implode(' · ');
                                        @endphp
                                        <div class="border-l-2 pl-3 {{ $borderClass }} {{ $isRest ? 'border-dashed' : '' }}">
                                            @if($block->block_type !== BlockType::StraightSets || $meta)
                                                <div class="flex items-center gap-2 flex-wrap mb-1">
                                                    @if($block->block_type !== BlockType::StraightSets)
                                                        <span class="text-xs font-medium" style="color: var(--{{ $block->block_type->color() }}-600)">
                                                            {{ $block->block_type->label() }}
                                                        </span>
                                                    @endif
                                                    @if($meta)
                                                        <span class="text-xs text-zinc-400 dark:text-zinc-500">{{ $meta }}</span>
                                                    @endif
                                                </div>
                                            @endif

                                            @if($isRest && $block->work_interval)
                                                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">{{ Format::duration($block->work_interval) }}</p>
                                            @endif

                                            @unless($isRest)
                                                <div class="mt-1.5 space-y-0.5">
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
                                                </div>
                                            @endunless
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @else
        <x-empty-state icon="calendar" message="No upcoming workouts scheduled">
            <flux:button href="{{ route('workouts.create') }}" variant="primary">
                Schedule Workout
            </flux:button>
        </x-empty-state>
    @endif

</flux:card>
