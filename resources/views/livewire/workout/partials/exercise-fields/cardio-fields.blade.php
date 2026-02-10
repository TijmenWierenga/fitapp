@props(['prefix'])

<div class="grid grid-cols-4 gap-2">
    <flux:input type="number" wire:model="{{ $prefix }}.target_duration" label="Duration (s)" placeholder="1800" min="0" size="xs" />
    <flux:input type="number" wire:model="{{ $prefix }}.target_distance" label="m" placeholder="5000" min="0" step="1" size="xs" />
    <flux:input type="number" wire:model="{{ $prefix }}.target_pace_min" label="Min pace (s/km)" placeholder="300" min="0" size="xs" />
    <flux:input type="number" wire:model="{{ $prefix }}.target_pace_max" label="Max pace (s/km)" placeholder="330" min="0" size="xs" />
    <flux:input type="number" wire:model="{{ $prefix }}.target_heart_rate_zone" label="HR zone" placeholder="3" min="1" max="5" size="xs" />
    <flux:input type="number" wire:model="{{ $prefix }}.target_heart_rate_min" label="Min HR" placeholder="130" min="0" size="xs" />
    <flux:input type="number" wire:model="{{ $prefix }}.target_heart_rate_max" label="Max HR" placeholder="155" min="0" size="xs" />
    <flux:input type="number" wire:model="{{ $prefix }}.target_power" label="Watts" placeholder="200" min="0" size="xs" />
</div>
