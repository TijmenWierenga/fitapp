@props(['block', 'prefix'])

@php
    $type = \App\Enums\Workout\BlockType::from($block['block_type']);
@endphp

@if($type->fields())
    <div class="grid grid-cols-4 gap-2">
        @if($type->hasField('rounds'))
            <flux:input type="number" wire:model="{{ $prefix }}.rounds" :label="$type === \App\Enums\Workout\BlockType::Emom ? 'Intervals' : 'Rounds'" placeholder="3" min="1" size="xs" />
        @endif

        @if($type->hasField('rest_between_exercises'))
            <flux:input type="number" wire:model="{{ $prefix }}.rest_between_exercises" label="Rest b/w ex (s)" placeholder="30" min="0" size="xs" />
        @endif

        @if($type->hasField('rest_between_rounds'))
            <flux:input type="number" wire:model="{{ $prefix }}.rest_between_rounds" label="Rest b/w rnd (s)" placeholder="60" min="0" size="xs" />
        @endif

        @if($type->hasField('time_cap'))
            <flux:input type="number" wire:model="{{ $prefix }}.time_cap" label="Time cap (s)" placeholder="600" min="0" size="xs" />
        @endif

        @if($type->hasField('work_interval'))
            <flux:input type="number" wire:model="{{ $prefix }}.work_interval" :label="$type === \App\Enums\Workout\BlockType::Emom ? 'Every X sec' : 'Work (s)'" placeholder="40" min="0" size="xs" />
        @endif

        @if($type->hasField('rest_interval'))
            <flux:input type="number" wire:model="{{ $prefix }}.rest_interval" label="Rest (s)" placeholder="20" min="0" size="xs" />
        @endif
    </div>
@endif
