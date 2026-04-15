<?php

use App\Livewire\Settings\ConnectedAccounts;
use App\Models\ConnectedAccount;
use App\Models\User;
use Livewire\Livewire;

it('renders the connected accounts settings page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('connected-accounts.edit'))
        ->assertOk();
});

it('shows connect button when Strava is not connected', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ConnectedAccounts::class)
        ->assertSee('Connect Strava');
});

it('shows connected badge when Strava is linked', function () {
    $user = User::factory()->create();

    ConnectedAccount::factory()->for($user)->create();

    Livewire::actingAs($user)
        ->test(ConnectedAccounts::class)
        ->assertSee('Connected')
        ->assertSee('Disconnect Strava');
});

it('disconnects Strava account', function () {
    $user = User::factory()->create();

    ConnectedAccount::factory()->for($user)->create();

    Livewire::actingAs($user)
        ->test(ConnectedAccounts::class)
        ->call('disconnectStrava');

    $this->assertDatabaseEmpty('connected_accounts');
});

it('prevents disconnecting when user has no password', function () {
    $user = User::factory()->withoutPassword()->create();

    ConnectedAccount::factory()->for($user)->create();

    Livewire::actingAs($user)
        ->test(ConnectedAccounts::class)
        ->call('disconnectStrava')
        ->assertHasErrors('strava');
});
