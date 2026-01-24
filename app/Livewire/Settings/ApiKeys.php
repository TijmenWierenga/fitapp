<?php

namespace App\Livewire\Settings;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ApiKeys extends Component
{
    public string $name = '';

    public ?string $expiresAt = null;

    public ?string $newTokenPlainText = null;

    /**
     * Create a new API token for the authenticated user
     */
    public function createToken(): void
    {
        $user = auth()->user();

        // Check if user already has 5 tokens
        if ($user->tokens()->count() >= 5) {
            throw ValidationException::withMessages([
                'name' => __('You cannot create more than 5 API tokens.'),
            ]);
        }

        $validated = Validator::make([
            'name' => $this->name,
            'expiresAt' => $this->expiresAt,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'expiresAt' => ['nullable', 'date', 'after:today'],
        ])->validate();

        $expiryDate = $validated['expiresAt'] ? now()->parse($validated['expiresAt']) : null;

        $token = $user->createToken($validated['name'], ['*'], $expiryDate);

        $this->newTokenPlainText = $token->plainTextToken;

        $this->reset('name', 'expiresAt');
    }

    /**
     * Delete an API token
     */
    public function deleteToken(int $tokenId): void
    {
        $user = auth()->user();

        $user->tokens()->where('id', $tokenId)->delete();

        $this->reset('newTokenPlainText');
    }

    /**
     * Close the new token modal
     */
    public function closeTokenModal(): void
    {
        $this->reset('newTokenPlainText');
    }

    /**
     * Get the user's active API tokens
     */
    #[Computed]
    public function tokens(): \Illuminate\Database\Eloquent\Collection
    {
        return auth()->user()
            ->tokens()
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->orderByDesc('created_at')
            ->get();
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.settings.api-keys');
    }
}
