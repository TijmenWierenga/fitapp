<?php

use App\Livewire\Workout\Show;
use App\Models\Block;
use App\Models\BlockExercise;
use App\Models\CardioExercise;
use App\Models\ExerciseSet;
use App\Models\Section;
use App\Models\StrengthExercise;
use App\Models\User;
use App\Models\Workout;
use Livewire\Livewire;

it('shows session summary banner for imported workouts', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->create([
        'user_id' => $user->id,
        'source' => 'garmin_fit',
        'total_duration' => 3720,
        'total_distance' => 8500,
        'total_calories' => 523,
        'avg_heart_rate' => 148,
        'max_heart_rate' => 172,
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->assertSee('1h 2min')
        ->assertSee('8.5 km')
        ->assertSee('523 kcal')
        ->assertSee('148 bpm')
        ->assertSee('172 bpm');
});

it('does not show session summary banner when no session data exists', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->assertDontSee('kcal');
});

it('shows actual duration instead of estimated for imported workouts', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->create([
        'user_id' => $user->id,
        'total_duration' => 2700,
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->assertSee('45min');
});

it('shows imported badge for garmin fit imports', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->create([
        'user_id' => $user->id,
        'source' => 'garmin_fit',
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->assertSee('Imported');
});

it('does not show imported badge for non-imported workouts', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->create([
        'user_id' => $user->id,
        'source' => null,
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->assertDontSee('Imported');
});

it('renders strength exercise sets table when exercise sets exist', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->create(['user_id' => $user->id, 'source' => 'garmin_fit']);
    $section = Section::factory()->create(['workout_id' => $workout->id]);
    $block = Block::factory()->create(['section_id' => $section->id]);
    $strengthExercise = StrengthExercise::factory()->create(['target_sets' => 3, 'target_reps_max' => 10, 'target_weight' => 80.00]);
    $blockExercise = BlockExercise::factory()->create([
        'block_id' => $block->id,
        'name' => 'Bench Press',
        'exerciseable_type' => 'strength_exercise',
        'exerciseable_id' => $strengthExercise->id,
    ]);

    ExerciseSet::factory()->create(['block_exercise_id' => $blockExercise->id, 'set_number' => 1, 'reps' => 10, 'weight' => 80.00]);
    ExerciseSet::factory()->create(['block_exercise_id' => $blockExercise->id, 'set_number' => 2, 'reps' => 10, 'weight' => 80.00]);
    ExerciseSet::factory()->create(['block_exercise_id' => $blockExercise->id, 'set_number' => 3, 'reps' => 8, 'weight' => 85.00]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->assertSee('Bench Press')
        ->assertSee('80 kg')
        ->assertSee('85 kg')
        ->assertDontSee('3 sets of 10 reps');
});

it('renders cardio lap table when exercise sets exist', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->create(['user_id' => $user->id, 'source' => 'garmin_fit']);
    $section = Section::factory()->create(['workout_id' => $workout->id]);
    $block = Block::factory()->distanceDuration()->create(['section_id' => $section->id]);
    $cardioExercise = CardioExercise::factory()->create(['target_duration' => 1800, 'target_distance' => 5000]);
    $blockExercise = BlockExercise::factory()->create([
        'block_id' => $block->id,
        'name' => 'Running',
        'exerciseable_type' => 'cardio_exercise',
        'exerciseable_id' => $cardioExercise->id,
    ]);

    ExerciseSet::factory()->cardio()->create([
        'block_exercise_id' => $blockExercise->id,
        'set_number' => 1,
        'distance' => 1000,
        'duration' => 312,
        'avg_pace' => 312,
        'avg_heart_rate' => 142,
        'max_heart_rate' => 158,
    ]);
    ExerciseSet::factory()->cardio()->create([
        'block_exercise_id' => $blockExercise->id,
        'set_number' => 2,
        'distance' => 1000,
        'duration' => 305,
        'avg_pace' => 305,
        'avg_heart_rate' => 148,
        'max_heart_rate' => 162,
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->assertSee('Running')
        ->assertSee('1 km')
        ->assertSee('5:12 /km')
        ->assertSee('5:05 /km')
        ->assertSee('142')
        ->assertSee('148')
        ->assertSee('Total');
});

it('falls back to planned presentation when no exercise sets exist', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->create(['user_id' => $user->id]);
    $section = Section::factory()->create(['workout_id' => $workout->id]);
    $block = Block::factory()->create(['section_id' => $section->id]);
    $strengthExercise = StrengthExercise::factory()->create([
        'target_sets' => 3,
        'target_reps_min' => 8,
        'target_reps_max' => 12,
        'target_weight' => 80.00,
    ]);
    BlockExercise::factory()->create([
        'block_id' => $block->id,
        'name' => 'Squat',
        'exerciseable_type' => 'strength_exercise',
        'exerciseable_id' => $strengthExercise->id,
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->assertSee('3 sets of 8-12 reps')
        ->assertSee('80 kg');
});

it('only shows session summary metrics that have values', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->create([
        'user_id' => $user->id,
        'total_duration' => 1800,
        'total_distance' => null,
        'total_calories' => null,
        'avg_heart_rate' => 140,
        'max_heart_rate' => null,
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->assertSee('30min')
        ->assertSee('140 bpm')
        ->assertDontSee('kcal');
});
