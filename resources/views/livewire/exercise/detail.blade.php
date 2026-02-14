<div>
    @if($exercise)
        <flux:modal name="exercise-detail" wire:model.live="showModal" @close="closeModal" class="max-w-2xl">
            <div class="space-y-6">
                {{-- Header --}}
                <div>
                    <flux:heading size="lg">{{ $exercise->name }}</flux:heading>
                    @if($exercise->aliases)
                        <flux:subheading>{{ implode(', ', $exercise->aliases) }}</flux:subheading>
                    @endif
                </div>

                {{-- Properties --}}
                <div class="flex flex-wrap gap-2">
                    @if($exercise->category)
                        <flux:badge size="sm" color="zinc">{{ ucfirst($exercise->category) }}</flux:badge>
                    @endif
                    @if($exercise->level)
                        <flux:badge size="sm" color="zinc">{{ ucfirst($exercise->level) }}</flux:badge>
                    @endif
                    @if($exercise->force)
                        <flux:badge size="sm" color="zinc">{{ ucfirst($exercise->force) }}</flux:badge>
                    @endif
                    @if($exercise->mechanic)
                        <flux:badge size="sm" color="zinc">{{ ucfirst($exercise->mechanic) }}</flux:badge>
                    @endif
                    @if($exercise->equipment)
                        <flux:badge size="sm" color="zinc">{{ ucfirst($exercise->equipment) }}</flux:badge>
                    @endif
                </div>

                {{-- Muscles --}}
                @if($exercise->primaryMuscles->isNotEmpty() || $exercise->secondaryMuscles->isNotEmpty())
                    <div class="space-y-3">
                        @if($exercise->primaryMuscles->isNotEmpty())
                            <div>
                                <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Primary muscles</span>
                                <div class="flex flex-wrap gap-1.5 mt-1">
                                    @foreach($exercise->primaryMuscles as $muscle)
                                        <flux:badge size="sm" color="accent">{{ $muscle->label }}</flux:badge>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        @if($exercise->secondaryMuscles->isNotEmpty())
                            <div>
                                <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Secondary muscles</span>
                                <div class="flex flex-wrap gap-1.5 mt-1">
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
                    <div>
                        <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Description</span>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $exercise->description }}</p>
                    </div>
                @endif

                {{-- Instructions --}}
                @if($exercise->instructions)
                    <div>
                        <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Instructions</span>
                        <ol class="mt-1 list-decimal list-inside space-y-1">
                            @foreach($exercise->instructions as $instruction)
                                <li class="text-sm text-zinc-600 dark:text-zinc-400">{{ $instruction }}</li>
                            @endforeach
                        </ol>
                    </div>
                @endif

                {{-- Tips --}}
                @if($exercise->tips)
                    <div>
                        <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Tips</span>
                        <ul class="mt-1 list-disc list-inside space-y-1">
                            @foreach($exercise->tips as $tip)
                                <li class="text-sm text-zinc-600 dark:text-zinc-400">{{ $tip }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Footer --}}
                <div class="flex justify-end pt-2">
                    <flux:button wire:click="closeModal" variant="ghost">
                        Close
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>
