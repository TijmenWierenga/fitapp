<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout
        :heading="__('API Keys')"
        :subheading="__('Manage your personal API keys for accessing the application programmatically')"
    >
        <div class="flex flex-col w-full mx-auto space-y-6 text-sm" wire:cloak>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <flux:badge :color="$tokens->count() >= 5 ? 'red' : 'zinc'">
                        {{ $tokens->count() }} of 5 keys used
                    </flux:badge>
                </div>

                <flux:button
                    variant="primary"
                    icon="key"
                    icon:variant="outline"
                    wire:click="openCreateModal"
                    :disabled="$tokens->count() >= 5"
                >
                    {{ __('Create API Key') }}
                </flux:button>
            </div>

            @error('tokenLimit')
                <flux:callout variant="danger" icon="x-circle" :heading="$message"/>
            @enderror

            @if($tokens->isNotEmpty())
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('Name') }}</flux:table.column>
                        <flux:table.column class="hidden sm:table-cell">{{ __('Created') }}</flux:table.column>
                        <flux:table.column class="hidden md:table-cell">{{ __('Last Used') }}</flux:table.column>
                        <flux:table.column align="end">{{ __('Actions') }}</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach($tokens as $token)
                            <flux:table.row :key="$token->id">
                                <flux:table.cell class="font-medium">{{ $token->name }}</flux:table.cell>
                                <flux:table.cell class="hidden sm:table-cell">
                                    {{ $token->created_at->diffForHumans() }}
                                </flux:table.cell>
                                <flux:table.cell class="hidden md:table-cell">
                                    @if($token->last_used_at)
                                        {{ $token->last_used_at->diffForHumans() }}
                                    @else
                                        <flux:badge color="zinc">{{ __('Never used') }}</flux:badge>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell align="end">
                                    <flux:button
                                        variant="danger"
                                        size="sm"
                                        wire:click="openRevokeModal({{ $token->id }})"
                                    >
                                        {{ __('Revoke') }}
                                    </flux:button>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @else
                <flux:callout variant="info" icon="information-circle">
                    {{ __("You haven't created any API keys yet. Create one to start using the API.") }}
                </flux:callout>
            @endif
        </div>
    </x-settings.layout>

    <flux:modal
        name="create-token"
        wire:model="showCreateModal"
        class="max-w-md"
    >
        <form wire:submit="createToken" class="space-y-6">
            <x-modal-icon icon="key" :heading="__('Create New API Key')" />

            <flux:field>
                <flux:label>{{ __('Token Name') }}</flux:label>
                <flux:input
                    wire:model="tokenName"
                    placeholder="e.g., Mobile App, CI/CD Pipeline"
                    autofocus
                />
                <flux:error name="tokenName"/>
            </flux:field>

            <div class="flex gap-3">
                <flux:button type="button" variant="outline" class="flex-1" wire:click="closeModals">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button type="submit" variant="primary" class="flex-1">
                    {{ __('Create') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal
        name="token-success"
        wire:model="showSuccessModal"
        class="max-w-2xl"
    >
        <div class="space-y-6">
            <x-modal-icon icon="check-circle" color="success" :heading="__('API Key Created')" />

            <flux:callout variant="warning" icon="exclamation-triangle">
                {{ __("This is the only time you'll see this key. Copy it and store it securely.") }}
            </flux:callout>

            <flux:input
                type="text"
                label="{{ __('Your API Key') }}"
                readonly
                copyable
                :value="$newToken"
                class="font-mono"
            />

            <flux:button variant="primary" class="w-full" wire:click="closeModals">
                {{ __("I've Saved My Key") }}
            </flux:button>
        </div>
    </flux:modal>

    <flux:modal
        name="revoke-token"
        wire:model="showRevokeModal"
        class="max-w-md"
    >
        <div class="space-y-6">
            <x-modal-icon
                icon="exclamation-triangle"
                color="danger"
                :heading="__('Revoke API Key?')"
                :description="__('This action cannot be undone. Any applications using this key will immediately lose access.')"
            />

            <div class="flex gap-3">
                <flux:button variant="outline" class="flex-1" wire:click="closeModals">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="danger" class="flex-1" wire:click="confirmRevoke">
                    {{ __('Revoke Key') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</section>
