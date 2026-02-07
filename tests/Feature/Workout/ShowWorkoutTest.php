<?php

use App\Livewire\Workout\Show;
use App\Models\Exercise;
use App\Models\ExerciseEntry;
use App\Models\ExerciseGroup;
use App\Models\IntervalBlock;
use App\Models\NoteBlock;
use App\Models\RestBlock;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutBlock;
use Livewire\Livewire;

// Authorization tests
it('allows viewing own workout', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('workouts.show', $workout))
        ->assertOk();
});

it('prevents viewing another users workout', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $workout = Workout::factory()->for($otherUser)->create();

    $this->actingAs($user)
        ->get(route('workouts.show', $workout))
        ->assertForbidden();
});

it('requires authentication', function () {
    $workout = Workout::factory()->create();

    $this->get(route('workouts.show', $workout))
        ->assertRedirect(route('login'));
});

// Display tests
it('displays workout details', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'name' => 'Morning Run',
        'scheduled_at' => now()->addDay(),
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->assertSee('Morning Run')
        ->assertSee($workout->scheduled_at->format('l, F j, Y'));
});

// Status badge tests
it('shows completed badge for completed workout', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'completed_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->assertSee('Completed');
});

it('shows overdue badge for past uncompleted workout', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => now()->subDay(),
        'completed_at' => null,
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->assertSee('Overdue');
});

it('shows today badge for workout scheduled today', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => now(),
        'completed_at' => null,
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->assertSee('Today');
});

it('shows tomorrow badge for workout scheduled tomorrow', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => now()->addDay(),
        'completed_at' => null,
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->assertSee('Tomorrow');
});

// Action tests
it('opens evaluation modal when clicking mark as completed', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => now(),
        'completed_at' => null,
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->call('openEvaluationModal')
        ->assertSet('showEvaluationModal', true);
});

it('shows edit button for editable workout', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'completed_at' => null,
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->assertSee('Edit Workout');
});

it('hides edit button for completed workout', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'completed_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->assertDontSee('Edit Workout');
});

it('can delete a deletable workout', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => now()->addDay(),
        'completed_at' => null,
    ]);

    expect(Workout::count())->toBe(1);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->call('deleteWorkout')
        ->assertRedirect(route('dashboard'));

    expect(Workout::count())->toBe(0);
});

it('cannot delete a completed workout', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'completed_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->call('deleteWorkout');

    expect(Workout::count())->toBe(1);
});

it('shows duplicate button', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create();

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->assertSee('Duplicate');
});

it('shows mark as completed button for today workout', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => now(),
        'completed_at' => null,
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->assertSee('Mark as Completed');
});

it('hides mark as completed button for completed workout', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => now(),
        'completed_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->assertDontSee('Mark as Completed');
});

it('hides mark as completed button for future workout', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => now()->addDays(2),
        'completed_at' => null,
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->assertDontSee('Mark as Completed');
});

it('shows delete button for deletable workout', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => now()->addDay(),
        'completed_at' => null,
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->assertSee('Delete');
});

it('hides delete button for completed workout', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => now(),
        'completed_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->assertDontSee('Delete');
});

// Workout Evaluation tests
it('marks workout as completed with valid evaluation and saves both values', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => now(),
        'completed_at' => null,
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->set('rpe', 7)
        ->set('feeling', 4)
        ->call('submitEvaluation')
        ->assertSet('showEvaluationModal', false)
        ->assertDispatched('workout-completed');

    $workout->refresh();

    expect($workout->completed_at)->not->toBeNull()
        ->and($workout->rpe)->toBe(7)
        ->and($workout->feeling)->toBe(4);
});

it('fails validation when attempting to complete without rpe', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => now(),
        'completed_at' => null,
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->set('feeling', 4)
        ->call('submitEvaluation')
        ->assertHasErrors(['rpe' => 'required']);

    expect($workout->fresh()->completed_at)->toBeNull();
});

it('fails validation when attempting to complete without feeling', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => now(),
        'completed_at' => null,
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->set('rpe', 7)
        ->call('submitEvaluation')
        ->assertHasErrors(['feeling' => 'required']);

    expect($workout->fresh()->completed_at)->toBeNull();
});

it('fails validation when rpe value is below 1', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => now(),
        'completed_at' => null,
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->set('rpe', 0)
        ->set('feeling', 4)
        ->call('submitEvaluation')
        ->assertHasErrors(['rpe' => 'min']);

    expect($workout->fresh()->completed_at)->toBeNull();
});

it('fails validation when rpe value is above 10', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => now(),
        'completed_at' => null,
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->set('rpe', 11)
        ->set('feeling', 4)
        ->call('submitEvaluation')
        ->assertHasErrors(['rpe' => 'max']);

    expect($workout->fresh()->completed_at)->toBeNull();
});

it('fails validation when feeling value is below 1', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => now(),
        'completed_at' => null,
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->set('rpe', 7)
        ->set('feeling', 0)
        ->call('submitEvaluation')
        ->assertHasErrors(['feeling' => 'min']);

    expect($workout->fresh()->completed_at)->toBeNull();
});

it('fails validation when feeling value is above 5', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => now(),
        'completed_at' => null,
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->set('rpe', 7)
        ->set('feeling', 6)
        ->call('submitEvaluation')
        ->assertHasErrors(['feeling' => 'max']);

    expect($workout->fresh()->completed_at)->toBeNull();
});

it('canceling evaluation keeps workout uncompleted', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => now(),
        'completed_at' => null,
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->call('openEvaluationModal')
        ->assertSet('showEvaluationModal', true)
        ->set('rpe', 7)
        ->set('feeling', 4)
        ->call('cancelEvaluation')
        ->assertSet('showEvaluationModal', false)
        ->assertSet('rpe', null)
        ->assertSet('feeling', null);

    expect($workout->fresh()->completed_at)->toBeNull();
});

// Block tree display tests
it('renders a multi-level block tree with all block types', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => now()->addDay(),
    ]);

    // Root group block
    $groupBlock = WorkoutBlock::factory()->group('Warm-up')->for($workout)->create([
        'position' => 0,
        'repeat_count' => 2,
        'rest_between_repeats_seconds' => 60,
    ]);

    // Nested interval under group
    $intervalBlockable = IntervalBlock::factory()->create([
        'duration_seconds' => 300,
        'intensity' => \App\Enums\Workout\IntervalIntensity::Easy,
    ]);
    WorkoutBlock::factory()->interval()->for($workout)->create([
        'parent_id' => $groupBlock->id,
        'position' => 0,
        'blockable_type' => 'interval_block',
        'blockable_id' => $intervalBlockable->id,
    ]);

    // Root exercise group block
    $exerciseGroup = ExerciseGroup::factory()->superset()->create();
    $exerciseGroupBlock = WorkoutBlock::factory()->exerciseGroup()->for($workout)->create([
        'position' => 1,
        'blockable_type' => 'exercise_group',
        'blockable_id' => $exerciseGroup->id,
    ]);

    // Exercise entry
    $exercise = Exercise::factory()->create(['name' => 'Bench Press']);
    ExerciseEntry::factory()->weighted(80.0)->create([
        'exercise_group_id' => $exerciseGroup->id,
        'exercise_id' => $exercise->id,
        'sets' => 4,
        'reps' => 8,
        'rpe_target' => 7,
        'rest_between_sets_seconds' => 120,
    ]);

    // Root rest block
    $restBlockable = RestBlock::factory()->create(['duration_seconds' => 120]);
    WorkoutBlock::factory()->rest()->for($workout)->create([
        'position' => 2,
        'blockable_type' => 'rest_block',
        'blockable_id' => $restBlockable->id,
    ]);

    // Root note block
    $noteBlockable = NoteBlock::factory()->create(['content' => 'Focus on form today']);
    WorkoutBlock::factory()->note()->for($workout)->create([
        'position' => 3,
        'blockable_type' => 'note_block',
        'blockable_id' => $noteBlockable->id,
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->assertSee('Workout Structure')
        ->assertSee('Warm-up')
        ->assertSee('2x')
        ->assertSee('Group')
        ->assertSee('Interval')
        ->assertSee('Easy')
        ->assertSee('5min')
        ->assertSee('Exercise Group')
        ->assertSee('Superset')
        ->assertSee('Bench Press')
        ->assertSee('80 kg')
        ->assertSee('RPE 7')
        ->assertSee('Rest')
        ->assertSee('2min')
        ->assertSee('Focus on form today');
});

it('shows empty state when workout has no blocks', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => now()->addDay(),
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->assertSee('No workout structure defined');
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
        ->test(Show::class, ['workout' => $workout])
        ->assertSee('RPE: 8/10 (Hard)')
        ->assertSee('Feeling: Good (4/5)');
});

it('returns correct rpe label', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'scheduled_at' => now(),
        'completed_at' => null,
    ]);

    $component = Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout]);

    $component->set('rpe', 1);
    expect($component->get('rpeLabel'))->toBe('Very Easy');

    $component->set('rpe', 4);
    expect($component->get('rpeLabel'))->toBe('Easy');

    $component->set('rpe', 6);
    expect($component->get('rpeLabel'))->toBe('Moderate');

    $component->set('rpe', 8);
    expect($component->get('rpeLabel'))->toBe('Hard');

    $component->set('rpe', 10);
    expect($component->get('rpeLabel'))->toBe('Maximum Effort');
});
