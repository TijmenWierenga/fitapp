<?php

use App\Enums\Workout\Sport;
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

test('it defaults to running sport', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Builder::class)
        ->assertSet('sport', 'running');
});

test('it loads sport when editing a workout', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->strength()->create(['user_id' => $user->id]);

    $this->actingAs($user);

    Livewire::test(Builder::class, ['workout' => $workout])
        ->assertSet('sport', Sport::Strength->value);
});

test('it can select a different sport', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Builder::class)
        ->call('removeStep', '0') // Remove default step first so no confirmation needed
        ->call('selectSport', Sport::Cardio->value)
        ->assertSet('sport', Sport::Cardio->value);
});

test('it shows confirmation modal when changing from running to other sport with existing steps', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Builder::class)
        ->assertCount('steps', 1) // Default step from mount
        ->call('selectSport', Sport::Strength->value)
        ->assertSet('showingActivityTypeChangeModal', true)
        ->assertSet('pendingSport', Sport::Strength->value)
        ->assertSet('sport', 'running'); // Sport should not change yet
});

test('it clears steps when confirming sport change from running to other', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Builder::class)
        ->assertCount('steps', 1) // Default step from mount
        ->call('selectSport', Sport::Strength->value)
        ->assertSet('showingActivityTypeChangeModal', true)
        ->call('confirmSportChange')
        ->assertSet('sport', Sport::Strength->value)
        ->assertSet('showingActivityTypeChangeModal', false)
        ->assertCount('steps', 0);
});

test('it keeps sport when canceling sport change', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Builder::class)
        ->assertCount('steps', 1)
        ->call('selectSport', Sport::Strength->value)
        ->assertSet('showingActivityTypeChangeModal', true)
        ->call('cancelSportChange')
        ->assertSet('sport', 'running')
        ->assertSet('showingActivityTypeChangeModal', false)
        ->assertCount('steps', 1);
});

test('it adds default step when changing to running from other sport', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Builder::class)
        ->call('removeStep', '0') // Remove default step
        ->set('sport', Sport::Cardio->value)
        ->assertCount('steps', 0)
        ->call('selectSport', Sport::Running->value)
        ->assertCount('steps', 1);
});

test('it can save a workout with a non-running sport', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Builder::class)
        ->call('removeStep', '0') // Remove default step
        ->call('selectSport', Sport::Strength->value)
        ->set('name', 'Leg Day')
        ->set('scheduled_date', '2026-01-01')
        ->set('scheduled_time', '08:00')
        ->call('saveWorkout')
        ->assertRedirect(route('dashboard'));

    $this->assertDatabaseHas('workouts', [
        'name' => 'Leg Day',
        'sport' => Sport::Strength->value,
    ]);

    $workout = Workout::where('name', 'Leg Day')->first();
    expect($workout->sport)->toBe(Sport::Strength);
    expect($workout->steps)->toHaveCount(0);
});

test('validation does not require steps for non-running sports', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Builder::class)
        ->call('removeStep', '0')
        ->call('selectSport', Sport::Cardio->value)
        ->set('name', 'Cycling Session')
        ->set('scheduled_date', '2026-01-01')
        ->set('scheduled_time', '08:00')
        ->assertCount('steps', 0)
        ->call('saveWorkout')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard'));
});

test('validation requires steps for running sport', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Builder::class)
        ->call('removeStep', '0')
        ->set('name', 'Morning Run')
        ->set('scheduled_date', '2026-01-01')
        ->set('scheduled_time', '08:00')
        ->call('saveWorkout')
        ->assertHasErrors(['steps']);
});
