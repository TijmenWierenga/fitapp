<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\OAuthProvider;
use App\Models\ConnectedAccount;
use App\Models\User;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class LinkOAuthAccount
{
    public function execute(User $user, OAuthProvider $provider, SocialiteUser $socialiteUser): ConnectedAccount
    {
        return $user->connectedAccounts()->updateOrCreate(
            [
                'provider' => $provider->value,
            ],
            [
                'provider_user_id' => $socialiteUser->getId(),
                'name' => $socialiteUser->getName(),
                'email' => $socialiteUser->getEmail(),
                'avatar' => $socialiteUser->getAvatar(),
                'access_token' => $socialiteUser->token,
                'refresh_token' => $socialiteUser->refreshToken,
                'token_expires_at' => $socialiteUser->expiresIn
                    ? now()->addSeconds($socialiteUser->expiresIn)
                    : null,
                'scopes' => $provider->defaultScopes(),
            ],
        );
    }
}
