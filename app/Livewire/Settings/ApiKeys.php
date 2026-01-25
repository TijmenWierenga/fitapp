<?php

namespace App\Livewire\Settings;

use Illuminate\Support\Collection;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;

class ApiKeys extends Component
{
    public bool $showCreateModal = false;

    public bool $showSuccessModal = false;

    #[Locked]
    public ?string $newToken = null;

    #[Validate('required|string|max:255')]
    public string $tokenName = '';

    public Collection $tokens;

    public ?int $tokenToRevoke = null;

    public bool $showRevokeModal = false;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->loadTokens();
    }

    /**
     * Load the user's API tokens.
     */
    public function loadTokens(): void
    {
        $this->tokens = auth()->user()->tokens()
            ->select(['id', 'name', 'created_at', 'last_used_at'])
            ->latest()
            ->get();
    }

    /**
     * Open the create token modal.
     */
    public function openCreateModal(): void
    {
        if (auth()->user()->tokens()->count() >= 5) {
            $this->addError('tokenLimit', 'Maximum of 5 API keys reached.');

            return;
        }

        $this->showCreateModal = true;
    }

    /**
     * Create a new API token.
     */
    public function createToken(): void
    {
        $this->validate();

        if (auth()->user()->tokens()->count() >= 5) {
            $this->addError('tokenLimit', 'Maximum of 5 API keys reached.');

            return;
        }

        $token = auth()->user()->createToken($this->tokenName);
        $this->newToken = $token->plainTextToken;

        $this->showCreateModal = false;
        $this->showSuccessModal = true;
        $this->tokenName = '';

        $this->loadTokens();
    }

    /**
     * Open the revoke token modal.
     */
    public function openRevokeModal(int $tokenId): void
    {
        $this->tokenToRevoke = $tokenId;
        $this->showRevokeModal = true;
    }

    /**
     * Confirm and revoke the token.
     */
    public function confirmRevoke(): void
    {
        auth()->user()->tokens()
            ->where('id', $this->tokenToRevoke)
            ->delete();

        $this->closeModals();
        $this->loadTokens();

        $this->dispatch('token-revoked');
    }

    /**
     * Close all modals and reset state.
     */
    public function closeModals(): void
    {
        $this->reset(
            'showCreateModal',
            'showSuccessModal',
            'showRevokeModal',
            'newToken',
            'tokenName',
            'tokenToRevoke',
        );

        $this->resetErrorBag();
    }

    /**
     * Clear sensitive data when success modal is closed.
     */
    public function updatedShowSuccessModal(bool $value): void
    {
        if (! $value) {
            $this->newToken = null;
        }
    }
}
