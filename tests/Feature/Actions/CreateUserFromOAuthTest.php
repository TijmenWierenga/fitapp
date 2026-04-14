<?php

use App\Actions\CreateUserFromOAuth;
use App\Enums\OAuthProvider;
use App\Models\ConnectedAccount;
use App\Models\User;
use App\Support\OAuth\PendingSocialiteUser;
use Laravel\Socialite\Contracts\User as SocialiteUser;

it('creates a user and connected account from OAuth data', function () {
    $socialiteUser = new PendingSocialiteUser([
        'provider_user_id' => '12345',
        'token' => 'access-token-123',
        'refresh_token' => 'refresh-token-123',
        'expires_in' => 21600,
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'avatar' => 'https://example.com/avatar.jpg',
    ]);

    $user = app(CreateUserFromOAuth::class)->execute(OAuthProvider::Strava, $socialiteUser);

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->name)->toBe('John Doe')
        ->and($user->email)->toBe('john@example.com')
        ->and($user->connectedAccounts)->toHaveCount(1);

    $account = $user->connectedAccounts->first();

    expect($account)->toBeInstanceOf(ConnectedAccount::class)
        ->and($account->provider)->toBe('strava')
        ->and($account->provider_user_id)->toBe('12345')
        ->and($account->name)->toBe('John Doe')
        ->and($account->email)->toBe('john@example.com')
        ->and($account->avatar)->toBe('https://example.com/avatar.jpg')
        ->and($account->access_token)->toBe('access-token-123')
        ->and($account->refresh_token)->toBe('refresh-token-123')
        ->and($account->token_expires_at)->not->toBeNull()
        ->and($account->scopes)->toBe(['read']);
});

it('sets password to null for OAuth-created users', function () {
    $socialiteUser = new PendingSocialiteUser([
        'provider_user_id' => '12345',
        'token' => 'access-token-123',
        'refresh_token' => 'refresh-token-123',
        'expires_in' => 21600,
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'avatar' => 'https://example.com/avatar.jpg',
    ]);

    $user = app(CreateUserFromOAuth::class)->execute(OAuthProvider::Strava, $socialiteUser);

    expect($user->password)->toBeNull()
        ->and($user->hasPassword())->toBeFalse();
});

it('falls back to nickname when name is null', function () {
    $socialiteUser = Mockery::mock(SocialiteUser::class);
    $socialiteUser->shouldReceive('getName')->andReturn(null);
    $socialiteUser->shouldReceive('getNickname')->andReturn('johndoe');
    $socialiteUser->shouldReceive('getEmail')->andReturn('john@example.com');
    $socialiteUser->shouldReceive('getId')->andReturn('12345');
    $socialiteUser->shouldReceive('getAvatar')->andReturn(null);
    $socialiteUser->token = 'access-token-123';
    $socialiteUser->refreshToken = 'refresh-token-123';
    $socialiteUser->expiresIn = 21600;

    $user = app(CreateUserFromOAuth::class)->execute(OAuthProvider::Strava, $socialiteUser);

    expect($user->name)->toBe('johndoe');
});

it('falls back to "Athlete" when both name and nickname are null', function () {
    $socialiteUser = Mockery::mock(SocialiteUser::class);
    $socialiteUser->shouldReceive('getName')->andReturn(null);
    $socialiteUser->shouldReceive('getNickname')->andReturn(null);
    $socialiteUser->shouldReceive('getEmail')->andReturn('john@example.com');
    $socialiteUser->shouldReceive('getId')->andReturn('12345');
    $socialiteUser->shouldReceive('getAvatar')->andReturn(null);
    $socialiteUser->token = 'access-token-123';
    $socialiteUser->refreshToken = 'refresh-token-123';
    $socialiteUser->expiresIn = 21600;

    $user = app(CreateUserFromOAuth::class)->execute(OAuthProvider::Strava, $socialiteUser);

    expect($user->name)->toBe('Athlete');
});
