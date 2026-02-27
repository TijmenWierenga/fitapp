<?php

use App\Livewire\Workout\ExerciseSearch;
use App\Models\Exercise;
use App\Models\MuscleGroup;
use App\Models\User;
use Livewire\Livewire;

it('opens modal on event dispatch and stores coordinates', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ExerciseSearch::class)
        ->assertSet('showModal', false)
        ->dispatch('open-exercise-search', sectionIndex: 1, blockIndex: 2)
        ->assertSet('showModal', true)
        ->assertSet('targetSectionIndex', 1)
        ->assertSet('targetBlockIndex', 2)
        ->assertSet('step', 'search');
});

it('searches exercises by query', function () {
    $user = User::factory()->create();
    Exercise::factory()->create(['name' => 'Barbell Bench Press', 'category' => 'strength', 'equipment' => 'barbell']);
    Exercise::factory()->create(['name' => 'Barbell Squat', 'category' => 'strength', 'equipment' => 'barbell']);
    Exercise::factory()->create(['name' => 'Dumbbell Curl', 'category' => 'strength', 'equipment' => 'dumbbell']);

    $component = Livewire::actingAs($user)
        ->test(ExerciseSearch::class)
        ->dispatch('open-exercise-search', sectionIndex: 0, blockIndex: 0)
        ->set('query', 'Bench Press');

    $exercises = $component->get('exercises');
    expect($exercises)->toHaveCount(1);
    expect($exercises->first()->name)->toBe('Barbell Bench Press');
});

it('filters exercises by muscle group', function () {
    $user = User::factory()->create();
    $chest = MuscleGroup::factory()->create(['name' => 'chest', 'label' => 'Chest']);
    $legs = MuscleGroup::factory()->create(['name' => 'quadriceps', 'label' => 'Quadriceps']);

    $benchPress = Exercise::factory()->create(['name' => 'Bench Press']);
    $benchPress->muscleGroups()->attach($chest, ['load_factor' => 1.0]);

    $squat = Exercise::factory()->create(['name' => 'Squat']);
    $squat->muscleGroups()->attach($legs, ['load_factor' => 1.0]);

    $component = Livewire::actingAs($user)
        ->test(ExerciseSearch::class)
        ->dispatch('open-exercise-search', sectionIndex: 0, blockIndex: 0)
        ->call('setMuscleGroupFilter', 'chest');

    $exercises = $component->get('exercises');
    expect($exercises)->toHaveCount(1);
    expect($exercises->first()->name)->toBe('Bench Press');
});

it('toggles muscle group filter off when clicking active filter', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ExerciseSearch::class)
        ->dispatch('open-exercise-search', sectionIndex: 0, blockIndex: 0)
        ->call('setMuscleGroupFilter', 'chest')
        ->assertSet('muscleGroupFilter', 'chest')
        ->call('setMuscleGroupFilter', 'chest')
        ->assertSet('muscleGroupFilter', null);
});

it('selects catalogue exercise and transitions to configure step', function () {
    $user = User::factory()->create();
    $chest = MuscleGroup::factory()->create(['name' => 'chest', 'label' => 'Chest']);
    $exercise = Exercise::factory()->create(['name' => 'Bench Press', 'category' => 'strength']);
    $exercise->muscleGroups()->attach($chest, ['load_factor' => 1.0]);

    Livewire::actingAs($user)
        ->test(ExerciseSearch::class)
        ->dispatch('open-exercise-search', sectionIndex: 0, blockIndex: 0)
        ->call('selectExercise', $exercise->id)
        ->assertSet('step', 'configure')
        ->assertSet('selectedExerciseId', $exercise->id)
        ->assertSet('selectedName', 'Bench Press')
        ->assertSet('selectedType', 'strength')
        ->assertSet('selectedMuscleGroups', ['Chest']);
});

it('infers cardio exercise type from category', function () {
    $user = User::factory()->create();
    $exercise = Exercise::factory()->create(['name' => 'Running', 'category' => 'cardio']);

    Livewire::actingAs($user)
        ->test(ExerciseSearch::class)
        ->dispatch('open-exercise-search', sectionIndex: 0, blockIndex: 0)
        ->call('selectExercise', $exercise->id)
        ->assertSet('selectedType', 'cardio');
});

it('infers duration exercise type from stretching category', function () {
    $user = User::factory()->create();
    $exercise = Exercise::factory()->create(['name' => 'Hamstring Stretch', 'category' => 'stretching']);

    Livewire::actingAs($user)
        ->test(ExerciseSearch::class)
        ->dispatch('open-exercise-search', sectionIndex: 0, blockIndex: 0)
        ->call('selectExercise', $exercise->id)
        ->assertSet('selectedType', 'duration');
});

it('transitions to free-form step', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ExerciseSearch::class)
        ->dispatch('open-exercise-search', sectionIndex: 0, blockIndex: 0)
        ->set('query', 'Custom Move')
        ->call('goToFreeForm')
        ->assertSet('step', 'freeform')
        ->assertSet('selectedName', 'Custom Move')
        ->assertSet('selectedExerciseId', null);
});

it('validates name is required in free-form mode', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ExerciseSearch::class)
        ->dispatch('open-exercise-search', sectionIndex: 0, blockIndex: 0)
        ->call('goToFreeForm')
        ->set('selectedName', '')
        ->call('confirmFreeForm')
        ->assertHasErrors('selectedName');
});

it('confirms free-form and transitions to configure step', function () {
    $user = User::factory()->create();
    MuscleGroup::factory()->create(['name' => 'chest', 'label' => 'Chest']);

    Livewire::actingAs($user)
        ->test(ExerciseSearch::class)
        ->dispatch('open-exercise-search', sectionIndex: 0, blockIndex: 0)
        ->call('goToFreeForm')
        ->set('selectedName', 'Custom Press')
        ->set('selectedType', 'strength')
        ->call('toggleFreeFormMuscleGroup', 'Chest')
        ->call('confirmFreeForm')
        ->assertSet('step', 'configure')
        ->assertSet('selectedName', 'Custom Press')
        ->assertSet('selectedExerciseId', null)
        ->assertSet('selectedMuscleGroups', ['Chest']);
});

it('dispatches exercise-selected event with all params on add', function () {
    $user = User::factory()->create();
    $exercise = Exercise::factory()->create(['name' => 'Deadlift', 'category' => 'strength']);

    Livewire::actingAs($user)
        ->test(ExerciseSearch::class)
        ->dispatch('open-exercise-search', sectionIndex: 1, blockIndex: 2)
        ->call('selectExercise', $exercise->id)
        ->set('targetSets', 5)
        ->set('targetRepsMax', 3)
        ->set('targetWeight', 180.0)
        ->set('targetRpe', 9.0)
        ->set('restAfter', 180)
        ->set('exerciseNotes', 'Heavy day')
        ->call('addExercise')
        ->assertDispatched('exercise-selected');
});

it('closes modal after adding exercise', function () {
    $user = User::factory()->create();
    $exercise = Exercise::factory()->create(['name' => 'Squat', 'category' => 'strength']);

    Livewire::actingAs($user)
        ->test(ExerciseSearch::class)
        ->dispatch('open-exercise-search', sectionIndex: 0, blockIndex: 0)
        ->call('selectExercise', $exercise->id)
        ->call('addExercise')
        ->assertSet('showModal', false);
});

it('back to search resets configure state', function () {
    $user = User::factory()->create();
    $exercise = Exercise::factory()->create(['name' => 'Bench', 'category' => 'strength']);

    Livewire::actingAs($user)
        ->test(ExerciseSearch::class)
        ->dispatch('open-exercise-search', sectionIndex: 0, blockIndex: 0)
        ->call('selectExercise', $exercise->id)
        ->set('targetSets', 3)
        ->call('backToSearch')
        ->assertSet('step', 'search')
        ->assertSet('targetSets', null);
});

it('close modal resets all state', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ExerciseSearch::class)
        ->dispatch('open-exercise-search', sectionIndex: 1, blockIndex: 2)
        ->set('query', 'test')
        ->call('closeModal')
        ->assertSet('showModal', false)
        ->assertSet('query', '')
        ->assertSet('step', 'search');
});
