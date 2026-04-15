<?php

use App\Actions\LinkOAuthAccount;
use App\Enums\OAuthProvider;
use App\Models\ConnectedAccount;
use App\Models\User;
use App\Support\OAuth\PendingSocialiteUser;

it('creates a new connected account for the user', function () {
    $user = User::factory()->create();

    $socialiteUser = PendingSocialiteUser::fromArray([
        'provider_user_id' => '12345',
        'token' => 'access-token-123',
        'refresh_token' => 'refresh-token-123',
        'expires_in' => 21600,
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'avatar' => 'https://example.com/avatar.jpg',
    ]);

    $account = app(LinkOAuthAccount::class)->execute($user, OAuthProvider::Strava, $socialiteUser);

    expect($account)->toBeInstanceOf(ConnectedAccount::class)
        ->and($account->user_id)->toBe($user->id)
        ->and($account->provider)->toBe('strava')
        ->and($account->provider_user_id)->toBe('12345')
        ->and($account->name)->toBe('John Doe')
        ->and($account->email)->toBe('john@example.com')
        ->and($account->avatar)->toBe('https://example.com/avatar.jpg')
        ->and($account->access_token)->toBe('access-token-123')
        ->and($account->refresh_token)->toBe('refresh-token-123')
        ->and($account->token_expires_at)->not->toBeNull();
});

it('updates an existing connected account with new tokens', function () {
    $user = User::factory()->create();
    $existingAccount = ConnectedAccount::factory()->for($user)->create([
        'provider' => OAuthProvider::Strava->value,
        'provider_user_id' => '12345',
        'access_token' => 'old-access-token',
        'refresh_token' => 'old-refresh-token',
    ]);

    $socialiteUser = PendingSocialiteUser::fromArray([
        'provider_user_id' => '12345',
        'token' => 'new-access-token',
        'refresh_token' => 'new-refresh-token',
        'expires_in' => 21600,
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'avatar' => 'https://example.com/avatar.jpg',
    ]);

    $account = app(LinkOAuthAccount::class)->execute($user, OAuthProvider::Strava, $socialiteUser);

    expect($account->id)->toBe($existingAccount->id)
        ->and($account->access_token)->toBe('new-access-token')
        ->and($account->refresh_token)->toBe('new-refresh-token')
        ->and($user->connectedAccounts()->count())->toBe(1);
});

it('stores the correct scopes', function () {
    $user = User::factory()->create();

    $socialiteUser = PendingSocialiteUser::fromArray([
        'provider_user_id' => '12345',
        'token' => 'access-token-123',
        'refresh_token' => 'refresh-token-123',
        'expires_in' => 21600,
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'avatar' => 'https://example.com/avatar.jpg',
    ]);

    $account = app(LinkOAuthAccount::class)->execute($user, OAuthProvider::Strava, $socialiteUser);

    expect($account->scopes)->toBe(['read']);
});
