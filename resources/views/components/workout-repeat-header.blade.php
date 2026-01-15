@props(['repeatCount'])

<flux:table.row class="bg-zinc-50/50 dark:bg-white/5">
    <flux:table.cell colspan="3">
        <div class="flex items-center gap-2 text-sm font-bold text-zinc-800 dark:text-white ps-2">
            <flux:icon.arrow-path class="size-4" />
            Repeat {{ $repeatCount }}x
        </div>
    </flux:table.cell>
</flux:table.row>
