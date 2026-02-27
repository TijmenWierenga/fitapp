@props(['block', 'si', 'bi'])

@php
    $prefix = "sections.{$si}.blocks.{$bi}";
    $blockType = \App\Enums\Workout\BlockType::tryFrom($block['block_type']);
    $isRest = $block['block_type'] === 'rest';
@endphp

<div x-data="{ expanded: @js(count($block['exercises']) > 0 || !empty($block['notes'])) }" class="px-3 py-2">
    {{-- Block header row --}}
    <div class="flex items-center gap-2">
        <div wire:sort:handle class="cursor-grab text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
            <flux:icon.bars-2 class="size-3.5" />
        </div>

        <button
            type="button"
            x-on:click="expanded = !expanded"
            class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300"
        >
            <flux:icon.chevron-right class="size-3.5 transition-transform" x-bind:class="expanded && 'rotate-90'" />
        </button>

        <flux:select wire:model.live="{{ $prefix }}.block_type" size="xs" class="w-40">
            @foreach(\App\Enums\Workout\BlockType::cases() as $type)
                <option value="{{ $type->value }}">{{ $type->label() }}</option>
            @endforeach
        </flux:select>

        @if($blockType)
            <flux:badge color="{{ $blockType->color() }}" size="sm">
                {{ $blockType->label() }}
            </flux:badge>
        @endif

        @if(!$isRest)
            <flux:text class="text-xs text-zinc-400">
                {{ count($block['exercises']) }} {{ str('exercise')->plural(count($block['exercises'])) }}
            </flux:text>
        @endif

        <div class="ml-auto flex items-center gap-1">
            @if(!$isRest)
                <flux:button
                    x-on:click="$dispatch('open-exercise-search', { sectionIndex: {{ $si }}, blockIndex: {{ $bi }} })"
                    variant="ghost"
                    size="xs"
                    icon="plus"
                    title="Add exercise"
                />
            @endif
            <flux:button
                wire:click="removeBlock({{ $si }}, {{ $bi }})"
                variant="ghost"
                size="xs"
                icon="x-mark"
                class="text-red-500 hover:text-red-700"
                title="Remove block"
            />
        </div>
    </div>

    {{-- Expanded content --}}
    <div x-show="expanded" x-cloak class="pl-9 mt-2 space-y-2">
        @include('livewire.workout.partials.block-type-fields', ['block' => $block, 'prefix' => $prefix])

        @if(!empty($block['notes']) || true)
            <flux:input
                wire:model="{{ $prefix }}.notes"
                placeholder="Block notes (optional)"
                size="sm"
            />
        @endif

        @if(!$isRest && count($block['exercises']) > 0)
            <div wire:sort="sortExercises" class="space-y-1">
                @foreach($block['exercises'] as $ei => $exercise)
                    <div wire:key="{{ $exercise['_key'] }}" wire:sort:item="{{ $exercise['_key'] }}">
                        @include('livewire.workout.partials.exercise-editor', [
                            'exercise' => $exercise,
                            'si' => $si,
                            'bi' => $bi,
                            'ei' => $ei,
                        ])
                    </div>
                @endforeach
            </div>
        @elseif(!$isRest)
            <flux:text class="text-xs text-zinc-400 italic py-1">
                No exercises yet
            </flux:text>
        @endif
    </div>
</div>
