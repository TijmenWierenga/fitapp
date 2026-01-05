<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('creates a user', function () {
    $this->artisan('app:user-create', [
        'name' => 'Tijmen Wierenga',
        'email' => 'tijmen@example.com',
        'password' => 'password',
    ])->assertSuccessful();

    $this->assertDatabaseHas('users', [
        'name' => 'Tijmen Wierenga',
        'email' => 'tijmen@example.com',
    ]);

    $user = User::where('email', 'tijmen@example.com')->first();
    expect(Hash::check('password', $user->password))->toBeTrue();
});
