@props(['icon' => 'document', 'message'])

<div class="flex flex-col items-center justify-center py-8 text-center">
    <flux:icon :name="$icon" class="size-12 text-zinc-400 dark:text-zinc-600 mb-3" />
    <flux:text class="text-zinc-500 dark:text-zinc-400">
        {{ $message }}
    </flux:text>
    @if($slot->isNotEmpty())
        <div class="mt-4">
            {{ $slot }}
        </div>
    @endif
</div>
