<?php

use App\Enums\Workout\Activity;
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
        'activity' => 'run',
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

test('it can select a different activity', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Builder::class)
        ->call('removeStep', '0') // Remove default step first so no confirmation needed
        ->call('selectActivity', Activity::Cardio->value)
        ->assertSet('activity', Activity::Cardio);
});

test('it shows confirmation modal when changing from running to other activity with existing steps', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Builder::class)
        ->assertCount('steps', 1) // Default step from mount
        ->call('selectActivity', Activity::Strength->value)
        ->assertSet('showingActivityTypeChangeModal', true)
        ->assertSet('pendingActivity', Activity::Strength)
        ->assertSet('activity', Activity::Run); // Activity should not change yet
});

test('it clears steps when confirming activity change from running to other', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Builder::class)
        ->assertCount('steps', 1) // Default step from mount
        ->call('selectActivity', Activity::Strength->value)
        ->assertSet('showingActivityTypeChangeModal', true)
        ->call('confirmActivityChange')
        ->assertSet('activity', Activity::Strength)
        ->assertSet('showingActivityTypeChangeModal', false)
        ->assertCount('steps', 0);
});

test('it keeps activity when canceling activity change', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Builder::class)
        ->assertCount('steps', 1)
        ->call('selectActivity', Activity::Strength->value)
        ->assertSet('showingActivityTypeChangeModal', true)
        ->call('cancelActivityChange')
        ->assertSet('activity', Activity::Run)
        ->assertSet('showingActivityTypeChangeModal', false)
        ->assertCount('steps', 1);
});

test('it adds default step when changing to running from other activity', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Builder::class)
        ->call('removeStep', '0') // Remove default step
        ->set('activity', Activity::Cardio)
        ->assertCount('steps', 0)
        ->call('selectActivity', Activity::Run->value)
        ->assertCount('steps', 1);
});

test('it can save a workout with a non-running activity', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Builder::class)
        ->call('removeStep', '0') // Remove default step
        ->call('selectActivity', Activity::Strength->value)
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
    expect($workout->steps)->toHaveCount(0);
});

test('validation does not require steps for non-running activities', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Builder::class)
        ->call('removeStep', '0')
        ->call('selectActivity', Activity::Cardio->value)
        ->set('name', 'Cycling Session')
        ->set('scheduled_date', '2026-01-01')
        ->set('scheduled_time', '08:00')
        ->assertCount('steps', 0)
        ->call('saveWorkout')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard'));
});

test('validation requires steps for running activity', function () {
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

test('it deletes persisted steps when changing from running to non-running activity and saving', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->create(['user_id' => $user->id, 'activity' => Activity::Run]);
    $step = \App\Models\Step::factory()->create([
        'workout_id' => $workout->id,
        'step_kind' => StepKind::Run,
    ]);

    $this->actingAs($user);

    // Verify step exists
    expect($workout->steps()->count())->toBe(1);

    Livewire::test(Builder::class, ['workout' => $workout])
        ->assertCount('steps', 1)
        ->call('selectActivity', Activity::Strength->value)
        ->assertSet('showingActivityTypeChangeModal', true)
        ->call('confirmActivityChange')
        ->assertSet('activity', Activity::Strength)
        ->assertCount('steps', 0) // In-memory steps should be cleared
        ->set('name', 'Strength Workout')
        ->call('saveWorkout')
        ->assertRedirect(route('dashboard'));

    // Verify persisted steps are deleted
    $workout->refresh();
    expect($workout->activity)->toBe(Activity::Strength);
    expect($workout->steps()->count())->toBe(0);
});
