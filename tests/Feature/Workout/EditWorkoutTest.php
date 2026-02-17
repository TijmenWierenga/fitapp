<?php

use App\Livewire\Workout\Builder;
use App\Livewire\Workout\Preview;
use App\Models\User;
use App\Models\Workout;
use Livewire\Livewire;

it('displays edit links for uncompleted workouts on the dashboard', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'completed_at' => null,
        'scheduled_at' => now()->addDay(),
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(route('workouts.edit', $workout));
});

it('displays edit links for completed workouts in the preview modal', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'completed_at' => now(),
        'scheduled_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test(Preview::class)
        ->dispatch('show-workout-preview', workoutId: $workout->id)
        ->assertSee(route('workouts.edit', $workout));
});

it('allows editing an uncompleted workout', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'completed_at' => null,
    ]);

    $this->actingAs($user)
        ->get(route('workouts.edit', $workout))
        ->assertOk();

    Livewire::actingAs($user)
        ->test(Builder::class, ['workout' => $workout])
        ->assertSet('name', $workout->name);
});

it('allows editing a completed workout', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'completed_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('workouts.edit', $workout))
        ->assertOk();

    Livewire::actingAs($user)
        ->test(Builder::class, ['workout' => $workout])
        ->assertSet('name', $workout->name);
});

it('allows saving a workout that was completed in the background', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'completed_at' => null,
    ]);

    $component = Livewire::actingAs($user)
        ->test(Builder::class, ['workout' => $workout])
        ->set('name', 'My Workout');

    // Manually mark it as completed in the background
    $workout->update(['completed_at' => now()]);

    $component->call('saveWorkout')
        ->assertRedirect(route('workouts.show', $workout));
});
