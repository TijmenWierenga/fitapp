<?php

use App\Actions\UnlinkOAuthAccount;
use App\Enums\OAuthProvider;
use App\Exceptions\CannotUnlinkOAuthAccountException;
use App\Models\ConnectedAccount;
use App\Models\User;

it('deletes the connected account', function () {
    $user = User::factory()->create();
    ConnectedAccount::factory()->for($user)->create([
        'provider' => OAuthProvider::Strava->value,
    ]);

    app(UnlinkOAuthAccount::class)->execute($user, OAuthProvider::Strava);

    expect($user->connectedAccounts()->count())->toBe(0);
});

it('throws when user has no password', function () {
    $user = User::factory()->withoutPassword()->create();
    ConnectedAccount::factory()->for($user)->create([
        'provider' => OAuthProvider::Strava->value,
    ]);

    app(UnlinkOAuthAccount::class)->execute($user, OAuthProvider::Strava);
})->throws(CannotUnlinkOAuthAccountException::class);

it('does not affect other providers connected accounts', function () {
    $user = User::factory()->create();

    $stravaAccount = ConnectedAccount::factory()->for($user)->create([
        'provider' => OAuthProvider::Strava->value,
    ]);

    $otherAccount = ConnectedAccount::factory()->for($user)->create([
        'provider' => 'other_provider',
    ]);

    app(UnlinkOAuthAccount::class)->execute($user, OAuthProvider::Strava);

    expect($user->connectedAccounts()->count())->toBe(1)
        ->and($user->connectedAccounts()->first()->id)->toBe($otherAccount->id);
});
