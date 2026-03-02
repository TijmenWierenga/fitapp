<?php

use App\Models\User;
use App\Notifications\WelcomeNotification;
use Illuminate\Support\Facades\Notification;

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertSuccessful();
});

test('new users can register', function () {
    Notification::fake();

    $response = $this->post(route('register.store'), [
        'name' => 'John Doe',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect('/coach?intake=1');

    $this->assertAuthenticated();
});

test('welcome notification is sent after registration', function () {
    Notification::fake();

    $this->post(route('register.store'), [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = User::where('email', 'jane@example.com')->first();

    Notification::assertSentTo($user, WelcomeNotification::class);
});

test('unverified users are redirected to verification notice', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertRedirect(route('verification.notice'));
});
