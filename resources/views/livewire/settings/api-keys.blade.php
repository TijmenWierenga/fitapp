<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout
        :heading="__('API Keys')"
        :subheading="__('Manage API tokens for MCP server access')">

        {{-- Create Token Form --}}
        <form wire:submit="createToken" class="my-6 w-full space-y-6">
            <flux:input
                wire:model="name"
                :label="__('Token Name')"
                placeholder="e.g., Claude Desktop, AI Coach"
                required />

            <div>
                <flux:field>
                    <flux:label>{{ __('Expiration (Optional)') }}</flux:label>
                    <flux:date-picker
                        wire:model="expiresAt"
                        :min="now()->addDay()->toDateString()"
                        placeholder="Never expires" />
                </flux:field>
                <flux:text class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                    {{ __('Leave empty for tokens that never expire. You can revoke tokens anytime.') }}
                </flux:text>
            </div>

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit">
                    {{ __('Create Token') }}
                </flux:button>
            </div>
        </form>

        {{-- Token List --}}
        <div class="mt-8 space-y-4">
            <flux:heading size="lg">{{ __('Active Tokens') }}</flux:heading>

            @forelse($this->tokens as $token)
                <flux:card>
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <flux:heading size="sm">{{ $token->name }}</flux:heading>
                            <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                                Created {{ $token->created_at->diffForHumans() }}
                                @if($token->expires_at)
                                    • Expires {{ $token->expires_at->diffForHumans() }}
                                @else
                                    • Never expires
                                @endif
                            </flux:text>
                            @if($token->last_used_at)
                                <flux:text class="text-sm text-zinc-500">
                                    Last used {{ $token->last_used_at->diffForHumans() }}
                                </flux:text>
                            @endif
                        </div>
                        <flux:button
                            variant="ghost"
                            size="sm"
                            wire:click="deleteToken({{ $token->id }})"
                            wire:confirm="Are you sure you want to delete this token? This action cannot be undone.">
                            <flux:icon.trash class="size-4" />
                        </flux:button>
                    </div>
                </flux:card>
            @empty
                <flux:text class="text-zinc-600 dark:text-zinc-400">
                    {{ __('No API tokens created yet.') }}
                </flux:text>
            @endforelse

            <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                {{ __('You can create up to 5 API tokens.') }}
            </flux:text>
        </div>

        {{-- New Token Modal --}}
        @if($newTokenPlainText)
            <flux:modal wire:model="newTokenPlainText" class="space-y-6">
                <flux:heading size="lg">{{ __('API Token Created') }}</flux:heading>

                <div class="space-y-4">
                    <flux:text>
                        {{ __('Please copy your new API token. For security reasons, it won\'t be shown again.') }}
                    </flux:text>

                    <div class="p-4 bg-zinc-100 dark:bg-zinc-800 rounded-lg font-mono text-sm break-all">
                        {{ $newTokenPlainText }}
                    </div>

                    <flux:text class="text-sm text-amber-600 dark:text-amber-400">
                        ⚠️ {{ __('Store this token securely. You will not be able to see it again.') }}
                    </flux:text>
                </div>

                <flux:button wire:click="closeTokenModal" variant="primary">
                    {{ __('Done') }}
                </flux:button>
            </flux:modal>
        @endif
    </x-settings.layout>
</section>
