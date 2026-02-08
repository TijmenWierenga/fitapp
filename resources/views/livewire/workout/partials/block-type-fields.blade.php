@props(['block', 'prefix'])

@php
    $blockType = $block['block_type'];
    $showRounds = in_array($blockType, ['circuit', 'superset', 'interval', 'emom']);
    $showRestBetween = in_array($blockType, ['circuit', 'superset']);
    $showTimeCap = in_array($blockType, ['amrap', 'for_time']);
    $showIntervals = in_array($blockType, ['interval', 'emom']);
@endphp

@if($showRounds || $showRestBetween || $showTimeCap || $showIntervals)
    <div class="grid grid-cols-4 gap-2">
        @if($showRounds)
            <flux:input type="number" wire:model="{{ $prefix }}.rounds" label="Rounds" placeholder="3" min="1" size="xs" />
        @endif

        @if($showRestBetween)
            <flux:input type="number" wire:model="{{ $prefix }}.rest_between_exercises" label="Rest b/w ex (s)" placeholder="30" min="0" size="xs" />
            <flux:input type="number" wire:model="{{ $prefix }}.rest_between_rounds" label="Rest b/w rnd (s)" placeholder="60" min="0" size="xs" />
        @endif

        @if($showTimeCap)
            <flux:input type="number" wire:model="{{ $prefix }}.time_cap" label="Time cap (s)" placeholder="600" min="0" size="xs" />
        @endif

        @if($showIntervals)
            <flux:input type="number" wire:model="{{ $prefix }}.work_interval" label="Work (s)" placeholder="40" min="0" size="xs" />
            @if($blockType === 'interval')
                <flux:input type="number" wire:model="{{ $prefix }}.rest_interval" label="Rest (s)" placeholder="20" min="0" size="xs" />
            @endif
        @endif
    </div>
@endif
