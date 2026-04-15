<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateUserFromOAuth;
use App\Actions\LinkOAuthAccount;
use App\Enums\OAuthProvider;
use App\Models\ConnectedAccount;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Laravel\Socialite\Facades\Socialite;

class StravaAuthController
{
    public function redirect(Request $request): RedirectResponse
    {
        $intent = $request->query('intent', 'login');

        $state = Crypt::encryptString(json_encode([
            'intent' => $intent,
            'user_id' => Auth::id(),
            'expires_at' => now()->addMinutes(10)->timestamp,
        ]));

        return Socialite::driver('strava')
            ->scopes(['read'])
            ->stateless()
            ->with(['state' => $state])
            ->redirect();
    }

    public function callback(
        Request $request,
        CreateUserFromOAuth $createUser,
        LinkOAuthAccount $linkAccount,
    ): RedirectResponse {
        try {
            $state = json_decode(Crypt::decryptString($request->query('state', '')), true);
        } catch (\Illuminate\Contracts\Encryption\DecryptException) {
            return redirect()->route('login')->withErrors([
                'email' => __('Invalid OAuth state. Please try again.'),
            ]);
        }

        $expiresAt = $state['expires_at'] ?? 0;

        if ($expiresAt < now()->timestamp) {
            return redirect()->route('login')->withErrors([
                'email' => __('OAuth session expired. Please try again.'),
            ]);
        }

        $intent = $state['intent'] ?? 'login';
        $userId = $state['user_id'] ?? null;

        $socialiteUser = Socialite::driver('strava')->stateless()->user();

        // Linking flow: user was authenticated when they started the flow
        if ($intent === 'link' && $userId) {
            $user = User::findOrFail($userId);
            $linkAccount->execute($user, OAuthProvider::Strava, $socialiteUser);

            return redirect()->route('connected-accounts.edit')
                ->with('status', 'strava-connected');
        }

        // Login flow: check if this Strava account is already linked
        $existingAccount = ConnectedAccount::query()
            ->where('provider', OAuthProvider::Strava->value)
            ->where('provider_user_id', $socialiteUser->getId())
            ->first();

        if ($existingAccount) {
            $linkAccount->execute($existingAccount->user, OAuthProvider::Strava, $socialiteUser);

            Auth::login($existingAccount->user, remember: true);

            return redirect()->intended(config('fortify.home'));
        }

        // Check for email collision
        $stravaEmail = $socialiteUser->getEmail();

        if ($stravaEmail) {
            $existingUser = User::where('email', $stravaEmail)->first();

            if ($existingUser) {
                $request->session()->put('strava_link_pending', [
                    'provider_user_id' => $socialiteUser->getId(),
                    'token' => $socialiteUser->token,
                    'refresh_token' => $socialiteUser->refreshToken,
                    'expires_in' => $socialiteUser->expiresIn,
                    'name' => $socialiteUser->getName(),
                    'email' => $socialiteUser->getEmail(),
                    'avatar' => $socialiteUser->getAvatar(),
                ]);

                return redirect()->route('auth.strava.confirm-link');
            }
        }

        // New user: require email from Strava to create account
        if (! $socialiteUser->getEmail()) {
            return redirect()->route('login')->withErrors([
                'email' => __('Your Strava account does not have an email address. Please update your Strava profile and try again.'),
            ]);
        }

        $user = $createUser->execute(OAuthProvider::Strava, $socialiteUser);

        Auth::login($user, remember: true);

        return redirect()->route('onboarding');
    }
}
