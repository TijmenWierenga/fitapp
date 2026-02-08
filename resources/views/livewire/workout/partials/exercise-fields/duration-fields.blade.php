@props(['prefix'])

<div class="grid grid-cols-4 gap-2">
    <flux:input type="number" wire:model="{{ $prefix }}.target_duration" label="Duration (s)" placeholder="60" min="0" size="xs" />
    <flux:input type="number" wire:model="{{ $prefix }}.target_rpe" label="RPE" placeholder="6" min="1" max="10" step="0.5" size="xs" />
</div>
