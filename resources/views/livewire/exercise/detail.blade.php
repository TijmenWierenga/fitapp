<div>
    @if($exercise)
        <flux:modal name="exercise-detail" wire:model.live="showModal" :closable="false" @close="closeModal" class="max-w-md">
            {{-- Header --}}
            <div class="flex items-center justify-between pb-4 mb-4 border-b border-zinc-200 dark:border-zinc-700">
                <div class="flex items-center gap-2.5">
                    <flux:icon.book-open class="size-5 text-accent" />
                    <span class="text-sm font-semibold text-zinc-900 dark:text-white">Exercise Details</span>
                </div>
                <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="closeModal" />
            </div>

            {{-- Title, Aliases & Properties --}}
            <div class="pb-4 mb-4 border-b border-zinc-200 dark:border-zinc-700 space-y-3">
                <h2 class="text-lg font-bold text-zinc-900 dark:text-white">{{ $exercise->name }}</h2>

                @if($exercise->aliases && count($exercise->aliases) > 0)
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="text-xs italic text-zinc-500">aka</span>
                        @foreach($exercise->aliases as $alias)
                            <flux:badge size="sm" color="zinc">{{ $alias }}</flux:badge>
                        @endforeach
                    </div>
                @endif

                @if($exercise->category || $exercise->level || $exercise->force || $exercise->mechanic || $exercise->equipment)
                    <div class="flex flex-wrap gap-1.5">
                        @foreach(array_filter([$exercise->category, $exercise->level, $exercise->force, $exercise->mechanic, $exercise->equipment]) as $prop)
                            <flux:badge size="sm" color="zinc">{{ ucfirst($prop) }}</flux:badge>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Muscle Groups --}}
            @if($exercise->primaryMuscles->isNotEmpty() || $exercise->secondaryMuscles->isNotEmpty())
                <div class="pb-4 mb-4 border-b border-zinc-200 dark:border-zinc-700 space-y-3">
                    @if($exercise->primaryMuscles->isNotEmpty())
                        <div class="flex items-center gap-3">
                            <span class="text-xs font-medium text-accent w-[70px] shrink-0">Primary</span>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($exercise->primaryMuscles as $muscle)
                                    <flux:badge size="sm" color="lime">{{ $muscle->label }}</flux:badge>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    @if($exercise->secondaryMuscles->isNotEmpty())
                        <div class="flex items-center gap-3">
                            <span class="text-xs font-medium text-zinc-500 w-[70px] shrink-0">Secondary</span>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($exercise->secondaryMuscles as $muscle)
                                    <flux:badge size="sm" color="zinc">{{ $muscle->label }}</flux:badge>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Description --}}
            @if($exercise->description)
                <div class="pb-4 mb-4 border-b border-zinc-200 dark:border-zinc-700">
                    <span class="text-[10px] font-medium uppercase tracking-wider text-zinc-500 mb-2 block">Description</span>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed">{{ $exercise->description }}</p>
                </div>
            @endif

            {{-- Instructions --}}
            @if($exercise->instructions && count($exercise->instructions) > 0)
                <div class="pb-4 mb-4 border-b border-zinc-200 dark:border-zinc-700">
                    <span class="text-[10px] font-medium uppercase tracking-wider text-zinc-500 mb-3 block">Instructions</span>
                    <div class="space-y-2.5">
                        @foreach($exercise->instructions as $index => $instruction)
                            <div class="flex gap-2.5">
                                <span class="flex items-center justify-center size-5 rounded-full bg-accent text-accent-foreground text-[10px] font-bold shrink-0 mt-0.5">{{ $index + 1 }}</span>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400 leading-relaxed">{{ $instruction }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Tips --}}
            @if($exercise->tips && count($exercise->tips) > 0)
                <div>
                    <span class="text-[10px] font-medium uppercase tracking-wider text-zinc-500 mb-3 block">Tips</span>
                    <div class="space-y-2.5">
                        @foreach($exercise->tips as $tip)
                            <div class="flex gap-2.5 rounded border border-accent/10 bg-accent/[0.03] dark:bg-accent/[0.03] p-3">
                                <flux:icon.light-bulb class="size-3.5 text-accent/60 shrink-0 mt-0.5" />
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed">{{ $tip }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </flux:modal>
    @endif
</div>
