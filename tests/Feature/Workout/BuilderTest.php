<?php

use App\Enums\Workout\StepKind;
use App\Livewire\Workout\Builder;
use App\Models\User;
use App\Models\Workout;
use Livewire\Livewire;

test('it loads steps when editing a workout', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->create(['user_id' => $user->id]);
    $step = \App\Models\Step::factory()->create([
        'workout_id' => $workout->id,
        'step_kind' => StepKind::Run,
    ]);

    $this->actingAs($user);

    Livewire::test(Builder::class, ['workout' => $workout])
        ->assertSet('name', $workout->name)
        ->assertCount('steps', 1)
        ->assertSet('steps.0.step_kind', StepKind::Run->value);
});

test('it can add a normal step', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Builder::class)
        ->call('addStep')
        ->assertCount('steps', 2); // mount adds 1 by default
});

test('it can add a repeat block with default children', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Builder::class)
        ->call('addRepeat')
        ->assertCount('steps', 2)
        ->assertSet('steps.1.step_kind', StepKind::Repeat->value)
        ->assertCount('steps.1.children', 2);
});

test('it can remove a step', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Builder::class)
        ->call('removeStep', '0')
        ->assertCount('steps', 0);
});

test('it can save a workout with nested steps', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Builder::class)
        ->set('name', 'Interval Workout')
        ->set('scheduled_date', '2026-01-01')
        ->set('scheduled_time', '08:00')
        ->call('addRepeat')
        ->call('saveWorkout')
        ->assertRedirect(route('dashboard'));

    $this->assertDatabaseHas('workouts', [
        'name' => 'Interval Workout',
        'sport' => 'running',
    ]);

    $workout = Workout::where('name', 'Interval Workout')->first();
    expect($workout->steps)->toHaveCount(4); // 1 default + (1 repeat + 2 children)
    expect($workout->rootSteps)->toHaveCount(2);
});

test('it does not throw exception when rendering badge with skip last recovery', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->create(['user_id' => $user->id]);
    $workout->steps()->create([
        'step_kind' => StepKind::Repeat,
        'repeat_count' => 3,
        'skip_last_recovery' => true,
        'sort_order' => 1,
    ]);

    Livewire::actingAs($user)
        ->test(Builder::class, ['workout' => $workout])
        ->assertStatus(200)
        ->assertSee('Skip last recovery');
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
