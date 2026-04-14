<x-layouts.auth>
    <div class="flex flex-col gap-6">
        <x-auth-header
            :title="__('Confirm your password')"
            :description="__('An account with this email already exists. Enter your password to link your Strava account.')"
        />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form wire:submit="confirmLink" class="flex flex-col gap-6">
            <flux:input
                wire:model="password"
                :label="__('Password')"
                type="password"
                required
                autocomplete="current-password"
                :placeholder="__('Password')"
                viewable
            />

            <flux:button variant="primary" type="submit" class="w-full">
                {{ __('Confirm & Link Strava') }}
            </flux:button>
        </form>

        <div class="space-x-1 text-sm text-center rtl:space-x-reverse text-zinc-600 dark:text-zinc-400">
            <flux:link :href="route('login')" wire:navigate>{{ __('Cancel') }}</flux:link>
        </div>
    </div>
</x-layouts.auth>
