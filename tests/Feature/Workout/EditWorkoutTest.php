<?php

use App\Livewire\Workout\Builder;
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

it('does not display edit links for completed workouts in the calendar', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'completed_at' => now(),
        'scheduled_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee(route('workouts.edit', $workout));
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

it('prevents editing a completed workout', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'completed_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('workouts.edit', $workout))
        ->assertForbidden();

    Livewire::actingAs($user)
        ->test(Builder::class, ['workout' => $workout])
        ->assertStatus(403);
});

it('prevents saving a completed workout', function () {
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
        ->assertStatus(403);
});
