<?php

use App\Enums\Workout\Activity;
use App\Livewire\Workout\Builder;
use App\Models\User;
use App\Models\Workout;
use Livewire\Livewire;

test('it loads workout metadata when editing', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user);

    Livewire::test(Builder::class, ['workout' => $workout])
        ->assertSet('name', $workout->name)
        ->assertSet('activity', $workout->activity);
});

test('validation rules for workout', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Builder::class)
        ->set('name', '')
        ->set('scheduled_date', '')
        ->set('scheduled_time', '')
        ->call('saveWorkout')
        ->assertHasErrors(['name', 'scheduled_date', 'scheduled_time']);
});

test('it defaults to run activity', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Builder::class)
        ->assertSet('activity', Activity::Run);
});

test('it loads activity when editing a workout', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->strength()->create(['user_id' => $user->id]);

    $this->actingAs($user);

    Livewire::test(Builder::class, ['workout' => $workout])
        ->assertSet('activity', Activity::Strength);
});

test('it can save a workout', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Builder::class)
        ->set('name', 'Morning Run')
        ->set('scheduled_date', '2026-01-01')
        ->set('scheduled_time', '08:00')
        ->call('saveWorkout')
        ->assertRedirect(route('dashboard'));

    $this->assertDatabaseHas('workouts', [
        'name' => 'Morning Run',
        'activity' => 'run',
    ]);
});

test('it can save a workout with a non-running activity', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Builder::class)
        ->set('activity', Activity::Strength)
        ->set('name', 'Leg Day')
        ->set('scheduled_date', '2026-01-01')
        ->set('scheduled_time', '08:00')
        ->call('saveWorkout')
        ->assertRedirect(route('dashboard'));

    $this->assertDatabaseHas('workouts', [
        'name' => 'Leg Day',
        'activity' => Activity::Strength->value,
    ]);

    $workout = Workout::where('name', 'Leg Day')->first();
    expect($workout->activity)->toBe(Activity::Strength);
});
