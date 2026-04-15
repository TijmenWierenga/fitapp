<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Actions\UnlinkOAuthAccount;
use App\Enums\OAuthProvider;
use App\Exceptions\CannotUnlinkOAuthAccountException;
use App\Models\ConnectedAccount;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ConnectedAccounts extends Component
{
    /**
     * Get the user's Strava connected account, if any.
     */
    #[Computed]
    public function stravaAccount(): ?ConnectedAccount
    {
        return Auth::user()
            ->connectedAccounts()
            ->where('provider', OAuthProvider::Strava->value)
            ->first();
    }

    public function disconnectStrava(UnlinkOAuthAccount $unlinkAccount): void
    {
        try {
            $unlinkAccount->execute(Auth::user(), OAuthProvider::Strava);
        } catch (CannotUnlinkOAuthAccountException $e) {
            $this->addError('strava', $e->getMessage());

            return;
        }

        unset($this->stravaAccount);

        session()->flash('status', 'strava-disconnected');
    }
}
