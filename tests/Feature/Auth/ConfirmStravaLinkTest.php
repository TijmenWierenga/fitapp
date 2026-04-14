<?php

use App\Enums\OAuthProvider;
use App\Livewire\Auth\ConfirmStravaLink;
use App\Models\User;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('redirects to login when no session data exists', function () {
    $this->get(route('auth.strava.confirm-link'))
        ->assertRedirect(route('login'));
});

it('links account and logs in with correct password', function () {
    $user = User::factory()->create([
        'email' => 'john@example.com',
        'password' => 'password',
    ]);

    Livewire::withQueryParams([])
        ->test(ConfirmStravaLink::class)
        ->assertRedirect(route('login'));

    session([
        'strava_link_pending' => [
            'provider_user_id' => '12345',
            'token' => 'access-token',
            'refresh_token' => 'refresh-token',
            'expires_in' => 21600,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'avatar' => 'https://example.com/avatar.jpg',
        ],
    ]);

    Livewire::test(ConfirmStravaLink::class)
        ->set('password', 'password')
        ->call('confirmLink')
        ->assertRedirect(config('fortify.home'));

    $this->assertAuthenticatedAs($user);

    expect($user->connectedAccounts()->where('provider', OAuthProvider::Strava->value)->exists())->toBeTrue();
});

it('shows error with incorrect password', function () {
    $user = User::factory()->create([
        'email' => 'john@example.com',
        'password' => 'password',
    ]);

    session([
        'strava_link_pending' => [
            'provider_user_id' => '12345',
            'token' => 'access-token',
            'refresh_token' => 'refresh-token',
            'expires_in' => 21600,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'avatar' => 'https://example.com/avatar.jpg',
        ],
    ]);

    Livewire::test(ConfirmStravaLink::class)
        ->set('password', 'wrong-password')
        ->call('confirmLink')
        ->assertHasErrors(['password']);

    $this->assertGuest();
});
