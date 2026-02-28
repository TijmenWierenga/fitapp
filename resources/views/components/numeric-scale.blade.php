@props(['min' => 0, 'max' => 10, 'wire' => '', 'selected' => null, 'size' => 'sm'])

<div class="flex gap-1">
    @foreach(range($min, $max) as $value)
        <button
            type="button"
            wire:click="$set('{{ $wire }}', {{ $value }})"
            @class([
                'flex-1 font-medium rounded-md transition-colors',
                'py-2 text-sm' => $size === 'sm',
                'py-1.5 text-xs' => $size === 'xs',
                'bg-accent text-accent-foreground' => $selected === $value,
                'bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-600' => $selected !== $value,
            ])
        >
            {{ $value }}
        </button>
    @endforeach
</div>
