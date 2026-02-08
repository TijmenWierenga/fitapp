@props(['prefix'])

<div class="grid grid-cols-4 gap-2">
    <flux:input type="number" wire:model="{{ $prefix }}.target_sets" label="Sets" placeholder="3" min="1" size="xs" />
    <flux:input type="number" wire:model="{{ $prefix }}.target_reps_min" label="Min reps" placeholder="8" min="0" size="xs" />
    <flux:input type="number" wire:model="{{ $prefix }}.target_reps_max" label="Max reps" placeholder="12" min="0" size="xs" />
    <flux:input type="number" wire:model="{{ $prefix }}.target_weight" label="kg" placeholder="80" min="0" step="0.5" size="xs" />
    <flux:input type="number" wire:model="{{ $prefix }}.target_rpe" label="RPE" placeholder="7" min="1" max="10" step="0.5" size="xs" />
    <flux:input wire:model="{{ $prefix }}.target_tempo" label="Tempo" placeholder="3-1-1-0" size="xs" />
    <flux:input type="number" wire:model="{{ $prefix }}.rest_after" label="Rest (s)" placeholder="90" min="0" size="xs" />
</div>
