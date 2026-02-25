<?php

use App\Livewire\Workout\Preview;
use App\Models\User;
use App\Models\Workout;
use Livewire\Livewire;

// Loading tests
it('loads workout via show-workout-preview event', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create();

    Livewire::actingAs($user)
        ->test(Preview::class)
        ->dispatch('show-workout-preview', workoutId: $workout->id)
        ->assertSet('showModal', true)
        ->assertSet('workout.id', $workout->id);
});

it('cannot load another users workout', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $workout = Workout::factory()->for($otherUser)->create();

    Livewire::actingAs($user)
        ->test(Preview::class)
        ->dispatch('show-workout-preview', workoutId: $workout->id);
})->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

// Display tests
it('displays workout name and date', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'name' => 'Evening Strength',
        'scheduled_at' => now()->addDay(),
    ]);

    Livewire::actingAs($user)
        ->test(Preview::class)
        ->dispatch('show-workout-preview', workoutId: $workout->id)
        ->assertSee('Evening Strength')
        ->assertSee($workout->scheduled_at->format('D, M j'));
});

it('shows View Full Page link', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create();

    Livewire::actingAs($user)
        ->test(Preview::class)
        ->dispatch('show-workout-preview', workoutId: $workout->id)
        ->assertSee('View Full Page');
});

// Status indicator tests
it('shows completed status for completed workout', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'completed_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test(Preview::class)
        ->dispatch('show-workout-preview', workoutId: $workout->id)
        ->assertSee('Completed');
});

it('shows overdue status for past uncompleted workout', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => now()->subDay(),
        'completed_at' => null,
    ]);

    Livewire::actingAs($user)
        ->test(Preview::class)
        ->dispatch('show-workout-preview', workoutId: $workout->id)
        ->assertSee('Overdue');
});

it('shows today status for workout scheduled today', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => now(),
        'completed_at' => null,
    ]);

    Livewire::actingAs($user)
        ->test(Preview::class)
        ->dispatch('show-workout-preview', workoutId: $workout->id)
        ->assertSee('Today');
});

it('shows tomorrow status for workout scheduled tomorrow', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => now()->addDay(),
        'completed_at' => null,
    ]);

    Livewire::actingAs($user)
        ->test(Preview::class)
        ->dispatch('show-workout-preview', workoutId: $workout->id)
        ->assertSee('Tomorrow');
});

// Evaluation flow tests
it('opens evaluation modal', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => now(),
        'completed_at' => null,
    ]);

    Livewire::actingAs($user)
        ->test(Preview::class)
        ->dispatch('show-workout-preview', workoutId: $workout->id)
        ->call('openEvaluationModal')
        ->assertSet('showEvaluationModal', true);
});

it('submits evaluation and keeps modal open with refreshed workout', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => now(),
        'completed_at' => null,
    ]);

    Livewire::actingAs($user)
        ->test(Preview::class)
        ->dispatch('show-workout-preview', workoutId: $workout->id)
        ->set('rpe', 7)
        ->set('feeling', 4)
        ->call('submitEvaluation')
        ->assertSet('showEvaluationModal', false)
        ->assertSet('showModal', true)
        ->assertDispatched('workout-completed');

    $workout->refresh();

    expect($workout->completed_at)->not->toBeNull()
        ->and($workout->rpe)->toBe(7)
        ->and($workout->feeling)->toBe(4);
});

it('fails validation without rpe', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => now(),
        'completed_at' => null,
    ]);

    Livewire::actingAs($user)
        ->test(Preview::class)
        ->dispatch('show-workout-preview', workoutId: $workout->id)
        ->set('feeling', 4)
        ->call('submitEvaluation')
        ->assertHasErrors(['rpe' => 'required']);

    expect($workout->fresh()->completed_at)->toBeNull();
});

it('fails validation without feeling', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => now(),
        'completed_at' => null,
    ]);

    Livewire::actingAs($user)
        ->test(Preview::class)
        ->dispatch('show-workout-preview', workoutId: $workout->id)
        ->set('rpe', 7)
        ->call('submitEvaluation')
        ->assertHasErrors(['feeling' => 'required']);

    expect($workout->fresh()->completed_at)->toBeNull();
});

it('cancels evaluation and resets state', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => now(),
        'completed_at' => null,
    ]);

    Livewire::actingAs($user)
        ->test(Preview::class)
        ->dispatch('show-workout-preview', workoutId: $workout->id)
        ->call('openEvaluationModal')
        ->set('rpe', 7)
        ->set('feeling', 4)
        ->call('cancelEvaluation')
        ->assertSet('showEvaluationModal', false)
        ->assertSet('rpe', null)
        ->assertSet('feeling', null);

    expect($workout->fresh()->completed_at)->toBeNull();
});

// Delete tests
it('deletes workout and dispatches event', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => now()->addDay(),
        'completed_at' => null,
    ]);

    expect(Workout::count())->toBe(1);

    Livewire::actingAs($user)
        ->test(Preview::class)
        ->dispatch('show-workout-preview', workoutId: $workout->id)
        ->call('deleteWorkout')
        ->assertDispatched('workout-deleted')
        ->assertSet('showModal', false)
        ->assertSet('workout', null);

    expect(Workout::count())->toBe(0);
});

// Close modal tests
it('closes modal and resets all state', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create();

    Livewire::actingAs($user)
        ->test(Preview::class)
        ->dispatch('show-workout-preview', workoutId: $workout->id)
        ->assertSet('showModal', true)
        ->call('closeModal')
        ->assertSet('showModal', false)
        ->assertSet('workout', null)
        ->assertSet('rpe', null)
        ->assertSet('feeling', null)
        ->assertSet('showEvaluationModal', false);
});

it('displays evaluation data for completed workout', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => now(),
        'completed_at' => now(),
        'rpe' => 8,
        'feeling' => 4,
    ]);

    Livewire::actingAs($user)
        ->test(Preview::class)
        ->dispatch('show-workout-preview', workoutId: $workout->id)
        ->assertSee('RPE: 8/10 (Hard)')
        ->assertSee('Feeling: Good (4/5)');
});

it('shows mark as completed button for today workout', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => now(),
        'completed_at' => null,
    ]);

    Livewire::actingAs($user)
        ->test(Preview::class)
        ->dispatch('show-workout-preview', workoutId: $workout->id)
        ->assertSee('Mark as Completed');
});

it('hides mark as completed button for future workout', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => now()->addDays(2),
        'completed_at' => null,
    ]);

    Livewire::actingAs($user)
        ->test(Preview::class)
        ->dispatch('show-workout-preview', workoutId: $workout->id)
        ->assertDontSee('Mark as Completed');
});
