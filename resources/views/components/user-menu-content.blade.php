<flux:menu.radio.group>
    <div class="p-0 text-sm font-normal">
        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
            <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                <span
                    class="flex h-full w-full items-center justify-center rounded-lg bg-zinc-200 text-zinc-900 dark:bg-zinc-700 dark:text-white"
                >
                    {{ auth()->user()->initials() }}
                </span>
            </span>

            <div class="grid flex-1 text-start text-sm leading-tight">
                <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                <span class="truncate text-xs">{{ auth()->user()->email }}</span>
            </div>
        </div>
    </div>
</flux:menu.radio.group>

<flux:menu.separator />

<flux:menu.radio.group>
    <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
</flux:menu.radio.group>

<flux:menu.separator />

<form method="POST" action="{{ route('logout') }}" class="w-full">
    @csrf
    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
        {{ __('Log Out') }}
    </flux:menu.item>
</form>
