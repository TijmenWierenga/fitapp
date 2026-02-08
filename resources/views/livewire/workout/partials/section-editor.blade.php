@props(['section', 'si'])

@php
    $prefix = "sections.{$si}";
@endphp

<div class="rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
    {{-- Section header row --}}
    <div class="flex items-center gap-2 bg-zinc-50 dark:bg-zinc-800 px-3 py-2">
        <div wire:sort:handle class="cursor-grab text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
            <flux:icon.bars-3 class="size-4" />
        </div>

        <div class="flex-1 flex items-center gap-2">
            <flux:input
                wire:model="{{ $prefix }}.name"
                placeholder="Section name (e.g. Warm-up)"
                size="sm"
                class="flex-1"
            />
            <flux:button
                wire:click="addBlock({{ $si }})"
                variant="subtle"
                size="xs"
                icon="plus"
            >
                Block
            </flux:button>
            <flux:button
                wire:click="removeSection({{ $si }})"
                variant="ghost"
                size="xs"
                icon="trash"
                class="text-red-500 hover:text-red-700"
            />
        </div>
    </div>
    <flux:error name="{{ $prefix }}.name" class="px-3 py-1" />

    {{-- Section notes (collapsible via x-show) --}}
    <div x-data="{ showNotes: @js(!empty($section['notes'])) }" class="px-3">
        <button
            type="button"
            x-on:click="showNotes = !showNotes"
            class="text-xs text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 py-1"
            x-text="showNotes ? 'Hide notes' : 'Add notes'"
        ></button>
        <div x-show="showNotes" x-cloak>
            <flux:textarea
                wire:model="{{ $prefix }}.notes"
                placeholder="Section notes..."
                rows="2"
                size="sm"
                class="mb-2"
            />
        </div>
    </div>

    {{-- Blocks --}}
    @if(count($section['blocks']) > 0)
        <div wire:sort="sortBlocks" class="divide-y divide-zinc-100 dark:divide-zinc-700/50">
            @foreach($section['blocks'] as $bi => $block)
                <div wire:key="{{ $block['_key'] }}" wire:sort:item="{{ $block['_key'] }}">
                    @include('livewire.workout.partials.block-editor', [
                        'block' => $block,
                        'si' => $si,
                        'bi' => $bi,
                    ])
                </div>
            @endforeach
        </div>
    @else
        <div class="px-3 py-3 text-center">
            <flux:text class="text-xs text-zinc-400 dark:text-zinc-500 italic">
                No blocks yet
            </flux:text>
        </div>
    @endif
</div>
