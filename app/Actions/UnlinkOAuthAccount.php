<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\OAuthProvider;
use App\Exceptions\CannotUnlinkOAuthAccountException;
use App\Models\User;

class UnlinkOAuthAccount
{
    public function execute(User $user, OAuthProvider $provider): void
    {
        if (! $user->hasPassword()) {
            throw new CannotUnlinkOAuthAccountException(
                'Cannot unlink the only authentication method. Please set a password first.'
            );
        }

        $user->connectedAccounts()
            ->where('provider', $provider->value)
            ->delete();
    }
}
