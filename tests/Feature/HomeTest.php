<?php

use App\Models\User;

test('guests see the welcome page', function () {
    $this->get('/')
        ->assertSuccessful()
        ->assertViewIs('welcome');
});

test('authenticated users are redirected to the dashboard', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/')
        ->assertRedirect('/dashboard');
});
