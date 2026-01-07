<?php

use App\Enums\Workout\StepKind;
use App\Livewire\Workout\Duplicate;
use App\Models\User;
use App\Models\Workout;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
    actingAs($this->user);
});

it('can duplicate a workout with a new date and time', function () {
    $workout = Workout::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Morning Run',
        'scheduled_at' => '2026-01-10 08:00:00',
    ]);

    Livewire::test(Duplicate::class)
        ->dispatch('duplicate-workout', workoutId: $workout->id)
        ->assertSet('showModal', true)
        ->assertSet('workout.id', $workout->id)
        ->set('scheduled_date', '2026-01-15')
        ->set('scheduled_time', '10:00')
        ->call('save')
        ->assertSet('showModal', false)
        ->assertDispatched('workout-duplicated');

    expect($this->user->workouts()->count())->toBe(2);

    $duplicatedWorkout = $this->user->workouts()->where('id', '!=', $workout->id)->first();
    expect($duplicatedWorkout)
        ->name->toBe('Morning Run')
        ->scheduled_at->format('Y-m-d H:i:s')->toBe('2026-01-15 10:00:00')
        ->completed_at->toBeNull();
});

it('pre-fills the form with the original workout date and time', function () {
    $workout = Workout::factory()->create([
        'user_id' => $this->user->id,
        'scheduled_at' => '2026-01-10 14:30:00',
    ]);

    Livewire::test(Duplicate::class)
        ->dispatch('duplicate-workout', workoutId: $workout->id)
        ->assertSet('scheduled_date', '2026-01-10')
        ->assertSet('scheduled_time', '14:30');
});

it('validates required date field', function () {
    $workout = Workout::factory()->create([
        'user_id' => $this->user->id,
    ]);

    Livewire::test(Duplicate::class)
        ->dispatch('duplicate-workout', workoutId: $workout->id)
        ->set('scheduled_date', '')
        ->set('scheduled_time', '10:00')
        ->call('save')
        ->assertHasErrors(['scheduled_date' => 'required']);
});

it('validates required time field', function () {
    $workout = Workout::factory()->create([
        'user_id' => $this->user->id,
    ]);

    Livewire::test(Duplicate::class)
        ->dispatch('duplicate-workout', workoutId: $workout->id)
        ->set('scheduled_date', '2026-01-15')
        ->set('scheduled_time', '')
        ->call('save')
        ->assertHasErrors(['scheduled_time' => 'required']);
});

it('closes modal when cancel is clicked', function () {
    $workout = Workout::factory()->create([
        'user_id' => $this->user->id,
    ]);

    Livewire::test(Duplicate::class)
        ->dispatch('duplicate-workout', workoutId: $workout->id)
        ->assertSet('showModal', true)
        ->call('closeModal')
        ->assertSet('showModal', false)
        ->assertSet('workout', null)
        ->assertSet('scheduled_date', '')
        ->assertSet('scheduled_time', '');
});

it('only allows users to duplicate their own workouts', function () {
    $otherUser = User::factory()->create();
    $workout = Workout::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    expect(fn () => Livewire::test(Duplicate::class)
        ->dispatch('duplicate-workout', workoutId: $workout->id))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
});

it('can duplicate a completed workout as an uncompleted workout', function () {
    $workout = Workout::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Yoga Session',
        'scheduled_at' => '2026-01-05 18:00:00',
        'completed_at' => '2026-01-05 18:45:00',
    ]);

    Livewire::test(Duplicate::class)
        ->dispatch('duplicate-workout', workoutId: $workout->id)
        ->set('scheduled_date', '2026-01-20')
        ->set('scheduled_time', '18:00')
        ->call('save')
        ->assertDispatched('workout-duplicated');

    $duplicatedWorkout = $this->user->workouts()->where('id', '!=', $workout->id)->first();
    expect($duplicatedWorkout)
        ->name->toBe('Yoga Session')
        ->completed_at->toBeNull();
});

it('duplicates steps when duplicating a workout', function () {
    $workout = Workout::factory()->create(['user_id' => $this->user->id]);
    \App\Models\Step::factory()->create([
        'workout_id' => $workout->id,
        'step_kind' => StepKind::Run,
    ]);

    Livewire::test(Duplicate::class)
        ->dispatch('duplicate-workout', workoutId: $workout->id)
        ->set('scheduled_date', '2026-01-15')
        ->set('scheduled_time', '10:00')
        ->call('save');

    $duplicatedWorkout = Workout::where('id', '!=', $workout->id)->first();
    expect($duplicatedWorkout->steps)->toHaveCount(1);
    expect($duplicatedWorkout->steps->first()->step_kind)->toBe(StepKind::Run);
});
