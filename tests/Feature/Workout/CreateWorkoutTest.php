<?php

use App\Livewire\Workout\Create;
use App\Models\User;
use Livewire\Livewire;

test('a user can create a workout', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Create::class)
        ->set('name', 'My new workout')
        ->set('scheduled_date', '2025-01-01')
        ->set('scheduled_time', '10:00:00')
        ->call('save')
        ->assertRedirect('/dashboard');

    $this->assertDatabaseHas('workouts', [
        'user_id' => $user->id,
        'name' => 'My new workout',
        'scheduled_at' => '2025-01-01 10:00:00',
    ]);
});

test('name is required', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Create::class)
        ->set('scheduled_date', '2025-01-01')
        ->set('scheduled_time', '10:00:00')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);
});

test('scheduled_date is required', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Create::class)
        ->set('name', 'My new workout')
        ->set('scheduled_time', '10:00:00')
        ->call('save')
        ->assertHasErrors(['scheduled_date' => 'required']);
});

test('scheduled_time is required', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Create::class)
        ->set('name', 'My new workout')
        ->set('scheduled_date', '2025-01-01')
        ->call('save')
        ->assertHasErrors(['scheduled_time' => 'required']);
});

test('scheduled_date must be a valid date', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Create::class)
        ->set('name', 'My new workout')
        ->set('scheduled_date', 'not-a-date')
        ->set('scheduled_time', '10:00:00')
        ->call('save')
        ->assertHasErrors(['scheduled_date' => 'date']);
});
