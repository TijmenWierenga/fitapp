<?php

use App\Models\User;
use App\Notifications\WelcomeNotification;

it('includes Traiq branding in email notifications', function () {
    $user = User::factory()->create();
    $notification = new WelcomeNotification;
    $rendered = (string) $notification->toMail($user)->render();

    expect($rendered)
        ->toContain('apple-touch-icon.png')
        ->toContain(config('app.name'));
});
