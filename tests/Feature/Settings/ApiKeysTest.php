<?php

use App\Livewire\Settings\ApiKeys;
use App\Models\User;
use Livewire\Livewire;

test('api keys page is displayed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('api-keys.edit'))
        ->assertSuccessful()
        ->assertSeeLivewire(ApiKeys::class);
});

test('user can create api token', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(ApiKeys::class)
        ->set('name', 'Test Token')
        ->call('createToken')
        ->assertSet('newTokenPlainText', fn ($value) => ! is_null($value));

    expect($user->tokens()->count())->toBe(1);
    expect($user->tokens()->first()->name)->toBe('Test Token');
});

test('user can create api token with expiry date', function () {
    $user = User::factory()->create();
    $expiryDate = now()->addMonths(3);

    $this->actingAs($user);

    Livewire::test(ApiKeys::class)
        ->set('name', 'Expiring Token')
        ->set('expiresAt', $expiryDate->toDateString())
        ->call('createToken');

    $token = $user->tokens()->first();
    expect($token->expires_at->toDateString())->toBe($expiryDate->toDateString());
});

test('user cannot create more than 5 tokens', function () {
    $user = User::factory()->create();

    // Create 5 tokens
    for ($i = 0; $i < 5; $i++) {
        $user->createToken("Token {$i}");
    }

    $this->actingAs($user);

    Livewire::test(ApiKeys::class)
        ->set('name', 'Sixth Token')
        ->call('createToken')
        ->assertHasErrors('name');

    expect($user->tokens()->count())->toBe(5);
});

test('user can delete their api token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('Test Token');

    $this->actingAs($user);

    Livewire::test(ApiKeys::class)
        ->call('deleteToken', $token->accessToken->id);

    expect($user->tokens()->count())->toBe(0);
});

test('user cannot delete another users token', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $token = $user1->createToken('User 1 Token');

    $this->actingAs($user2);

    Livewire::test(ApiKeys::class)
        ->call('deleteToken', $token->accessToken->id);

    // Token should still exist
    expect($user1->tokens()->count())->toBe(1);
});

test('token name is required', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(ApiKeys::class)
        ->set('name', '')
        ->call('createToken')
        ->assertHasErrors(['name' => 'required']);
});

test('expiry date must be in the future', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(ApiKeys::class)
        ->set('name', 'Test Token')
        ->set('expiresAt', now()->subDay()->toDateString())
        ->call('createToken')
        ->assertHasErrors(['expiresAt']);
});
