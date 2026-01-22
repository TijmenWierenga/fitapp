<?php

namespace App\Livewire\Settings;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Profile extends Component
{
    public string $name = '';

    public string $email = '';

    public ?string $timezone = null;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
        $this->timezone = Auth::user()->timezone ?? 'UTC';
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id),
            ],

            'timezone' => ['required', 'string', 'timezone:all'],
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('profile-updated', name: $user->name);
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    /**
     * Get a grouped list of timezones for the select dropdown.
     *
     * @return array<string, array<string, string>>
     */
    public function getTimezonesProperty(): array
    {
        $timezones = [];

        foreach (\DateTimeZone::listIdentifiers() as $timezone) {
            $parts = explode('/', $timezone, 2);

            if (count($parts) === 2) {
                $region = $parts[0];
                $city = str_replace('_', ' ', $parts[1]);
                $timezones[$region][$timezone] = $city;
            } else {
                $timezones['Other'][$timezone] = $timezone;
            }
        }

        return $timezones;
    }
}
