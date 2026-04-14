<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Actions\LinkOAuthAccount;
use App\Enums\OAuthProvider;
use App\Models\User;
use App\Support\OAuth\PendingSocialiteUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class ConfirmStravaLink extends Component
{
    public string $password = '';

    public function mount(): void
    {
        if (! session()->has('strava_link_pending')) {
            $this->redirect(route('login'), navigate: true);
        }
    }

    public function confirmLink(LinkOAuthAccount $linkAccount): void
    {
        $this->validate([
            'password' => ['required', 'string'],
        ]);

        $throttleKey = 'confirm-strava-link:'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'password' => [__('Too many attempts. Please try again in :seconds seconds.', ['seconds' => $seconds])],
            ]);
        }

        $pending = session('strava_link_pending');
        $user = User::where('email', $pending['email'])->first();

        if (! $user || ! Hash::check($this->password, $user->password)) {
            RateLimiter::hit($throttleKey, 60);

            $this->addError('password', __('The provided password is incorrect.'));

            return;
        }

        RateLimiter::clear($throttleKey);

        $socialiteUser = new PendingSocialiteUser($pending);

        $linkAccount->execute($user, OAuthProvider::Strava, $socialiteUser);

        session()->forget('strava_link_pending');

        Auth::login($user, remember: true);

        $this->redirect(config('fortify.home'), navigate: true);
    }
}
