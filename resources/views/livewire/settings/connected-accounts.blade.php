<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout
        :heading="__('Connected Accounts')"
        :subheading="__('Manage your third-party account connections.')"
    >
        <div class="space-y-6">
            @if (session('status') === 'strava-connected')
                <flux:callout variant="success">
                    {{ __('Strava account connected successfully.') }}
                </flux:callout>
            @endif

            @if (session('status') === 'strava-disconnected')
                <flux:callout variant="success">
                    {{ __('Strava account disconnected.') }}
                </flux:callout>
            @endif

            {{-- Strava card --}}
            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#FC4C02]">
                            <svg class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M15.387 17.944l-2.089-4.116h-3.065L15.387 24l5.15-10.172h-3.066m-7.008-5.599l2.836 5.598h4.172L10.463 0l-7 13.828h4.169"/>
                            </svg>
                        </div>
                        <div>
                            <flux:heading size="sm">{{ __('Strava') }}</flux:heading>
                            @if ($this->stravaAccount)
                                <flux:subheading size="sm">{{ $this->stravaAccount->name ?? $this->stravaAccount->email }}</flux:subheading>
                            @else
                                <flux:subheading size="sm">{{ __('Sync workouts, activities and performance data automatically.') }}</flux:subheading>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        @if ($this->stravaAccount)
                            <flux:badge color="green">{{ __('Connected') }}</flux:badge>
                            <flux:button
                                variant="danger"
                                size="sm"
                                wire:click="disconnectStrava"
                                wire:confirm="{{ __('Are you sure you want to disconnect your Strava account?') }}"
                            >
                                {{ __('Disconnect Strava') }}
                            </flux:button>
                        @else
                            <a href="{{ route('auth.strava.redirect', ['intent' => 'link']) }}">
                                <flux:button variant="primary" size="sm" class="!bg-[#FC4C02] hover:!bg-[#E34402]">
                                    {{ __('Connect Strava') }}
                                </flux:button>
                            </a>
                        @endif
                    </div>
                </div>

                @error('strava')
                    <div class="mt-3">
                        <flux:text class="text-sm text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                    </div>
                @enderror
            </div>
        </div>
    </x-settings.layout>
</section>
