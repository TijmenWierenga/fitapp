<?php

use App\Livewire\Workout\Builder;
use App\Models\Block;
use App\Models\BlockExercise;
use App\Models\Exercise;
use App\Models\Section;
use App\Models\StrengthExercise;
use App\Models\User;
use App\Models\Workout;
use Livewire\Livewire;

it('adds a section', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Builder::class)
        ->assertCount('sections', 3)
        ->call('addSection')
        ->assertCount('sections', 4);
});

it('removes a section', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Builder::class)
        ->assertCount('sections', 3)
        ->call('removeSection', 0)
        ->assertCount('sections', 2);
});

it('adds a block to a section', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Builder::class)
        ->call('addSection')
        ->call('addBlock', 0)
        ->assertCount('sections.0.blocks', 1);
});

it('removes a block from a section', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Builder::class)
        ->call('addSection')
        ->call('addBlock', 0)
        ->call('addBlock', 0)
        ->assertCount('sections.0.blocks', 2)
        ->call('removeBlock', 0, 0)
        ->assertCount('sections.0.blocks', 1);
});

it('adds an exercise to a block', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Builder::class)
        ->call('addSection')
        ->call('addBlock', 0)
        ->call('addExercise', 0, 0)
        ->assertCount('sections.0.blocks.0.exercises', 1);
});

it('removes an exercise from a block', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Builder::class)
        ->call('addSection')
        ->call('addBlock', 0)
        ->call('addExercise', 0, 0)
        ->call('addExercise', 0, 0)
        ->assertCount('sections.0.blocks.0.exercises', 2)
        ->call('removeExercise', 0, 0, 0)
        ->assertCount('sections.0.blocks.0.exercises', 1);
});

it('reorders sections via sortSections', function () {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(Builder::class)
        ->call('addSection')
        ->set('sections.0.name', 'First')
        ->call('addSection')
        ->set('sections.1.name', 'Second');

    $key = $component->get('sections.1._key');

    $component->call('sortSections', $key, 0)
        ->assertSet('sections.0.name', 'Second')
        ->assertSet('sections.1.name', 'First');
});

it('reorders blocks via sortBlocks', function () {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(Builder::class)
        ->call('addSection')
        ->call('addBlock', 0)
        ->set('sections.0.blocks.0.block_type', 'circuit')
        ->call('addBlock', 0)
        ->set('sections.0.blocks.1.block_type', 'interval');

    $key = $component->get('sections.0.blocks.1._key');

    $component->call('sortBlocks', $key, 0)
        ->assertSet('sections.0.blocks.0.block_type', 'interval')
        ->assertSet('sections.0.blocks.1.block_type', 'circuit');
});

it('reorders exercises via sortExercises', function () {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(Builder::class)
        ->call('addSection')
        ->call('addBlock', 0)
        ->call('addExercise', 0, 0)
        ->set('sections.0.blocks.0.exercises.0.name', 'Push-up')
        ->call('addExercise', 0, 0)
        ->set('sections.0.blocks.0.exercises.1.name', 'Squat');

    $key = $component->get('sections.0.blocks.0.exercises.1._key');

    $component->call('sortExercises', $key, 0)
        ->assertSet('sections.0.blocks.0.exercises.0.name', 'Squat')
        ->assertSet('sections.0.blocks.0.exercises.1.name', 'Push-up');
});

it('creates a workout with structure', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Builder::class)
        ->set('name', 'Test Workout')
        ->set('scheduled_date', '2026-03-01')
        ->set('scheduled_time', '08:00')
        ->call('addBlock', 0)
        ->set('sections.0.blocks.0.block_type', 'straight_sets')
        ->call('addExercise', 0, 0)
        ->set('sections.0.blocks.0.exercises.0.name', 'Push-up')
        ->set('sections.0.blocks.0.exercises.0.type', 'strength')
        ->set('sections.0.blocks.0.exercises.0.target_sets', 3)
        ->set('sections.0.blocks.0.exercises.0.target_reps_max', 15)
        ->call('saveWorkout')
        ->assertRedirect();

    $this->assertDatabaseHas('workouts', ['name' => 'Test Workout']);
    $this->assertDatabaseHas('sections', ['name' => 'Warm-up', 'order' => 0]);
    $this->assertDatabaseHas('block_exercises', ['name' => 'Push-up']);
    $this->assertDatabaseHas('strength_exercises', ['target_sets' => 3, 'target_reps_max' => 15]);
});

it('creates a workout without structure', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Builder::class)
        ->set('name', 'Simple Run')
        ->set('scheduled_date', '2026-03-01')
        ->set('scheduled_time', '07:00')
        ->call('removeSection', 2)
        ->call('removeSection', 1)
        ->call('removeSection', 0)
        ->call('saveWorkout')
        ->assertRedirect();

    $this->assertDatabaseHas('workouts', ['name' => 'Simple Run']);
    $this->assertDatabaseCount('sections', 0);
});

it('edits an existing workout and replaces structure', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create(['name' => 'Old Workout']);
    $section = Section::factory()->for($workout)->create(['name' => 'Old Section']);
    $block = Block::factory()->for($section)->create();
    $strength = StrengthExercise::factory()->create();
    BlockExercise::factory()->create([
        'block_id' => $block->id,
        'name' => 'Old Exercise',
        'exerciseable_type' => 'strength_exercise',
        'exerciseable_id' => $strength->id,
    ]);

    Livewire::actingAs($user)
        ->test(Builder::class, ['workout' => $workout])
        ->assertSet('name', 'Old Workout')
        ->assertCount('sections', 1)
        ->assertSet('sections.0.name', 'Old Section')
        ->set('name', 'Updated Workout')
        ->set('sections.0.name', 'Updated Section')
        ->set('sections.0.blocks.0.exercises.0.name', 'Updated Exercise')
        ->call('saveWorkout')
        ->assertRedirect();

    $this->assertDatabaseHas('workouts', ['id' => $workout->id, 'name' => 'Updated Workout']);
    $this->assertDatabaseHas('sections', ['name' => 'Updated Section']);
    $this->assertDatabaseHas('block_exercises', ['name' => 'Updated Exercise']);
    $this->assertDatabaseMissing('sections', ['name' => 'Old Section']);
});

it('hydrates sections from existing workout on edit', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create();
    $section = Section::factory()->for($workout)->create(['name' => 'Main']);
    $block = Block::factory()->circuit()->for($section)->create(['rounds' => 4]);
    $strength = StrengthExercise::factory()->create(['target_sets' => 5, 'target_weight' => 100]);
    BlockExercise::factory()->create([
        'block_id' => $block->id,
        'name' => 'Deadlift',
        'exerciseable_type' => 'strength_exercise',
        'exerciseable_id' => $strength->id,
    ]);

    $component = Livewire::actingAs($user)
        ->test(Builder::class, ['workout' => $workout]);

    $component
        ->assertCount('sections', 1)
        ->assertSet('sections.0.name', 'Main')
        ->assertSet('sections.0.blocks.0.block_type', 'circuit')
        ->assertSet('sections.0.blocks.0.rounds', 4)
        ->assertSet('sections.0.blocks.0.exercises.0.name', 'Deadlift')
        ->assertSet('sections.0.blocks.0.exercises.0.type', 'strength')
        ->assertSet('sections.0.blocks.0.exercises.0.target_sets', 5)
        ->assertSet('sections.0.blocks.0.exercises.0.target_weight', 100.0);
});

it('validates required section name', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Builder::class)
        ->set('name', 'Test')
        ->set('scheduled_date', '2026-03-01')
        ->set('scheduled_time', '08:00')
        ->set('sections.0.name', '')
        ->call('saveWorkout')
        ->assertHasErrors('sections.0.name');
});

it('validates required exercise name', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Builder::class)
        ->set('name', 'Test')
        ->set('scheduled_date', '2026-03-01')
        ->set('scheduled_time', '08:00')
        ->call('addSection')
        ->set('sections.0.name', 'Main')
        ->call('addBlock', 0)
        ->call('addExercise', 0, 0)
        ->call('saveWorkout')
        ->assertHasErrors('sections.0.blocks.0.exercises.0.name');
});

it('allows editing a completed workout', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->completed()->create();

    Livewire::actingAs($user)
        ->test(Builder::class, ['workout' => $workout])
        ->assertOk()
        ->assertSet('name', $workout->name);
});

it('redirects to workout show page after save', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Builder::class)
        ->set('name', 'Redirect Test')
        ->set('scheduled_date', '2026-03-01')
        ->set('scheduled_time', '08:00')
        ->call('saveWorkout')
        ->assertRedirectToRoute('workouts.show', Workout::latest()->first());
});

it('populates exercise from exercise-selected event with all params', function () {
    $user = User::factory()->create();
    $exercise = Exercise::factory()->create(['name' => 'Bench Press']);

    Livewire::actingAs($user)
        ->test(Builder::class)
        ->call('addSection')
        ->set('sections.0.name', 'Main')
        ->call('addBlock', 0)
        ->dispatch('exercise-selected', [
            'sectionIndex' => 0,
            'blockIndex' => 0,
            'exerciseId' => $exercise->id,
            'name' => 'Bench Press',
            'type' => 'strength',
            'targetSets' => 4,
            'targetRepsMax' => 8,
            'targetWeight' => 80.0,
            'targetRpe' => 8.0,
            'restAfter' => 90,
            'exerciseNotes' => 'Pause at bottom',
            'targetRepsMin' => null,
            'targetTempo' => null,
            'targetDuration' => null,
            'targetDistance' => null,
            'targetPaceMin' => null,
            'targetPaceMax' => null,
            'targetHeartRateZone' => null,
            'targetHeartRateMin' => null,
            'targetHeartRateMax' => null,
            'targetPower' => null,
        ])
        ->assertSet('sections.0.blocks.0.exercises.0.exercise_id', $exercise->id)
        ->assertSet('sections.0.blocks.0.exercises.0.name', 'Bench Press')
        ->assertSet('sections.0.blocks.0.exercises.0.type', 'strength')
        ->assertSet('sections.0.blocks.0.exercises.0.target_sets', 4)
        ->assertSet('sections.0.blocks.0.exercises.0.target_reps_max', 8)
        ->assertSet('sections.0.blocks.0.exercises.0.target_weight', 80.0)
        ->assertSet('sections.0.blocks.0.exercises.0.target_rpe', 8.0)
        ->assertSet('sections.0.blocks.0.exercises.0.rest_after', 90)
        ->assertSet('sections.0.blocks.0.exercises.0.notes', 'Pause at bottom');
});

it('saves exercise_id to database through workout save', function () {
    $user = User::factory()->create();
    $exercise = Exercise::factory()->create(['name' => 'Squat']);

    Livewire::actingAs($user)
        ->test(Builder::class)
        ->set('name', 'Test Workout')
        ->set('scheduled_date', '2026-03-01')
        ->set('scheduled_time', '08:00')
        ->call('addBlock', 0)
        ->dispatch('exercise-selected', [
            'sectionIndex' => 0,
            'blockIndex' => 0,
            'exerciseId' => $exercise->id,
            'name' => 'Squat',
            'type' => 'strength',
            'targetSets' => 5,
            'targetRepsMax' => 5,
            'targetWeight' => null,
            'targetRpe' => null,
            'targetRepsMin' => null,
            'targetTempo' => null,
            'restAfter' => null,
            'targetDuration' => null,
            'targetDistance' => null,
            'targetPaceMin' => null,
            'targetPaceMax' => null,
            'targetHeartRateZone' => null,
            'targetHeartRateMin' => null,
            'targetHeartRateMax' => null,
            'targetPower' => null,
            'exerciseNotes' => null,
        ])
        ->call('saveWorkout')
        ->assertRedirect();

    $this->assertDatabaseHas('block_exercises', [
        'name' => 'Squat',
        'exercise_id' => $exercise->id,
    ]);
});

it('hydrates exercise_id when editing existing workout', function () {
    $user = User::factory()->create();
    $exercise = Exercise::factory()->create(['name' => 'Deadlift']);
    $workout = Workout::factory()->for($user)->create();
    $section = Section::factory()->for($workout)->create(['name' => 'Main']);
    $block = Block::factory()->for($section)->create();
    $strength = StrengthExercise::factory()->create();
    BlockExercise::factory()->create([
        'block_id' => $block->id,
        'exercise_id' => $exercise->id,
        'name' => 'Deadlift',
        'exerciseable_type' => 'strength_exercise',
        'exerciseable_id' => $strength->id,
    ]);

    Livewire::actingAs($user)
        ->test(Builder::class, ['workout' => $workout])
        ->assertSet('sections.0.blocks.0.exercises.0.exercise_id', $exercise->id)
        ->assertSet('sections.0.blocks.0.exercises.0.name', 'Deadlift');
});

it('saves free-form exercise without exercise_id', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Builder::class)
        ->set('name', 'Test Workout')
        ->set('scheduled_date', '2026-03-01')
        ->set('scheduled_time', '08:00')
        ->call('addBlock', 0)
        ->dispatch('exercise-selected', [
            'sectionIndex' => 0,
            'blockIndex' => 0,
            'exerciseId' => null,
            'name' => 'Custom Press',
            'type' => 'strength',
            'targetSets' => 3,
            'targetRepsMax' => 12,
            'targetWeight' => null,
            'targetRpe' => null,
            'targetRepsMin' => null,
            'targetTempo' => null,
            'restAfter' => null,
            'targetDuration' => null,
            'targetDistance' => null,
            'targetPaceMin' => null,
            'targetPaceMax' => null,
            'targetHeartRateZone' => null,
            'targetHeartRateMin' => null,
            'targetHeartRateMax' => null,
            'targetPower' => null,
            'exerciseNotes' => null,
        ])
        ->call('saveWorkout')
        ->assertRedirect();

    $this->assertDatabaseHas('block_exercises', [
        'name' => 'Custom Press',
        'exercise_id' => null,
    ]);
});
