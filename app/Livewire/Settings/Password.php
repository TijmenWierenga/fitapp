<?php

namespace App\Livewire\Settings;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Password extends Component
{
    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    #[Locked]
    public bool $hasExistingPassword = true;

    public function mount(): void
    {
        $this->hasExistingPassword = Auth::user()->hasPassword();
    }

    /**
     * Update or set the password for the currently authenticated user.
     */
    public function updatePassword(): void
    {
        // Always re-check server-side — never trust the client property alone
        $userHasPassword = Auth::user()->hasPassword();

        try {
            $rules = [
                'password' => ['required', 'string', PasswordRule::defaults(), 'confirmed'],
            ];

            if ($userHasPassword) {
                $rules['current_password'] = ['required', 'string', 'current_password'];
            }

            $validated = $this->validate($rules);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => $validated['password'],
        ]);

        $this->hasExistingPassword = true;

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->dispatch('password-updated');
    }
}
