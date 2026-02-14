<div class="max-w-4xl mx-auto p-6">
    {{-- Header with back navigation --}}
    <div class="flex items-center gap-4 mb-6">
        <flux:button href="{{ route('dashboard') }}" variant="ghost" icon="arrow-left" />
        <div class="flex-1">
            <flux:heading size="xl">{{ $exercise->name }}</flux:heading>
            @if($exercise->aliases)
                <flux:subheading>{{ implode(', ', $exercise->aliases) }}</flux:subheading>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Description --}}
            @if($exercise->description)
                <flux:card>
                    <flux:heading size="lg" class="mb-3">Description</flux:heading>
                    <flux:text>{{ $exercise->description }}</flux:text>
                </flux:card>
            @endif

            {{-- Instructions --}}
            @if($exercise->instructions)
                <flux:card>
                    <flux:heading size="lg" class="mb-3">Instructions</flux:heading>
                    <ol class="list-decimal list-inside space-y-2">
                        @foreach($exercise->instructions as $instruction)
                            <li class="text-sm text-zinc-600 dark:text-zinc-400">{{ $instruction }}</li>
                        @endforeach
                    </ol>
                </flux:card>
            @endif

            {{-- Tips --}}
            @if($exercise->tips)
                <flux:card>
                    <flux:heading size="lg" class="mb-3">Tips</flux:heading>
                    <ul class="list-disc list-inside space-y-2">
                        @foreach($exercise->tips as $tip)
                            <li class="text-sm text-zinc-600 dark:text-zinc-400">{{ $tip }}</li>
                        @endforeach
                    </ul>
                </flux:card>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Properties --}}
            <flux:card>
                <flux:heading size="lg" class="mb-4">Details</flux:heading>
                <dl class="space-y-3">
                    @if($exercise->category)
                        <div>
                            <dt class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Category</dt>
                            <dd class="text-sm text-zinc-800 dark:text-zinc-200 capitalize">{{ $exercise->category }}</dd>
                        </div>
                    @endif
                    @if($exercise->level)
                        <div>
                            <dt class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Level</dt>
                            <dd class="text-sm text-zinc-800 dark:text-zinc-200 capitalize">{{ $exercise->level }}</dd>
                        </div>
                    @endif
                    @if($exercise->force)
                        <div>
                            <dt class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Force</dt>
                            <dd class="text-sm text-zinc-800 dark:text-zinc-200 capitalize">{{ $exercise->force }}</dd>
                        </div>
                    @endif
                    @if($exercise->mechanic)
                        <div>
                            <dt class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Mechanic</dt>
                            <dd class="text-sm text-zinc-800 dark:text-zinc-200 capitalize">{{ $exercise->mechanic }}</dd>
                        </div>
                    @endif
                    @if($exercise->equipment)
                        <div>
                            <dt class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Equipment</dt>
                            <dd class="text-sm text-zinc-800 dark:text-zinc-200 capitalize">{{ $exercise->equipment }}</dd>
                        </div>
                    @endif
                </dl>
            </flux:card>

            {{-- Muscles --}}
            @if($exercise->primaryMuscles->isNotEmpty() || $exercise->secondaryMuscles->isNotEmpty())
                <flux:card>
                    <flux:heading size="lg" class="mb-4">Muscles</flux:heading>
                    <div class="space-y-4">
                        @if($exercise->primaryMuscles->isNotEmpty())
                            <div>
                                <dt class="text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-2">Primary</dt>
                                <dd class="flex flex-wrap gap-1.5">
                                    @foreach($exercise->primaryMuscles as $muscle)
                                        <flux:badge size="sm" color="accent">{{ $muscle->label }}</flux:badge>
                                    @endforeach
                                </dd>
                            </div>
                        @endif
                        @if($exercise->secondaryMuscles->isNotEmpty())
                            <div>
                                <dt class="text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-2">Secondary</dt>
                                <dd class="flex flex-wrap gap-1.5">
                                    @foreach($exercise->secondaryMuscles as $muscle)
                                        <flux:badge size="sm" color="zinc">{{ $muscle->label }}</flux:badge>
                                    @endforeach
                                </dd>
                            </div>
                        @endif
                    </div>
                </flux:card>
            @endif
        </div>
    </div>
</div>
