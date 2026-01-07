@props(['step', 'path', 'loop'])

<flux:card class="p-3 flex justify-between items-center group">
    <div class="flex items-center gap-4">
        <div class="flex flex-col items-center justify-center w-8 text-gray-400">
             <div class="text-xs">{{ $loop->iteration }}</div>
        </div>
        <div>
            <div class="flex items-center gap-2">
                <flux:heading size="sm">{{ ucfirst($step['step_kind']) }}</flux:heading>
                @if(isset($step['name']) && $step['name'])
                    <flux:text size="xs" class="text-gray-500">({{ $step['name'] }})</flux:text>
                @endif
            </div>
            <div class="flex gap-3 mt-1">
                <flux:text size="sm" class="flex items-center gap-1">
                    <flux:icon.clock class="size-3" />
                    {{ \App\Support\Workout\StepSummary::duration($step) }}
                </flux:text>
                <flux:text size="sm" class="flex items-center gap-1">
                    <flux:icon.bolt class="size-3" />
                    {{ \App\Support\Workout\StepSummary::target($step) }}
                </flux:text>
            </div>
        </div>
    </div>
    <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
        <flux:button size="xs" variant="ghost" wire:click="moveUp('{{ $path }}')" :disabled="$loop->first" icon="chevron-up" />
        <flux:button size="xs" variant="ghost" wire:click="moveDown('{{ $path }}')" :disabled="$loop->last" icon="chevron-down" />
        <flux:button size="xs" variant="ghost" wire:click="editStep('{{ $path }}')">Edit</flux:button>
        <flux:button size="xs" variant="ghost" wire:click="removeStep('{{ $path }}')" class="text-red-500" icon="trash" />
    </div>
</flux:card>
