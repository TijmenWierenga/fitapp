<?php

use App\Livewire\Workout\Create;
use App\Models\User;
use Livewire\Livewire;

test('a user can create a workout', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Create::class)
        ->set('name', 'My new workout')
        ->set('scheduled_at', '2025-01-01 10:00:00')
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
        ->set('scheduled_at', '2025-01-01 10:00:00')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);
});

test('scheduled_at is required', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Create::class)
        ->set('name', 'My new workout')
        ->call('save')
        ->assertHasErrors(['scheduled_at' => 'required']);
});

test('scheduled_at must be a valid date', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Create::class)
        ->set('name', 'My new workout')
        ->set('scheduled_at', 'not-a-date')
        ->call('save')
        ->assertHasErrors(['scheduled_at' => 'date']);
});
