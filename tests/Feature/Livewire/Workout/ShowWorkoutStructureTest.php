<?php

use App\Livewire\Workout\Show;
use App\Models\Block;
use App\Models\BlockExercise;
use App\Models\CardioExercise;
use App\Models\DurationExercise;
use App\Models\Exercise;
use App\Models\Section;
use App\Models\StrengthExercise;
use App\Models\User;
use App\Models\Workout;
use Livewire\Livewire;

it('renders the workout structure card when sections exist', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->create(['user_id' => $user->id]);
    $section = Section::factory()->create(['workout_id' => $workout->id, 'name' => 'Warm-up']);
    $block = Block::factory()->create(['section_id' => $section->id]);
    $strengthExercise = StrengthExercise::factory()->create(['target_sets' => 3, 'target_reps_max' => 10]);
    BlockExercise::factory()->create([
        'block_id' => $block->id,
        'name' => 'Bench Press',
        'exerciseable_type' => 'strength_exercise',
        'exerciseable_id' => $strengthExercise->id,
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->assertSee('Workout Structure')
        ->assertSee('Warm-up')
        ->assertSee('Bench Press');
});

it('does not render the structure card when no sections exist', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->assertDontSee('Workout Structure');
});

it('renders strength exercise with labeled details', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->create(['user_id' => $user->id]);
    $section = Section::factory()->create(['workout_id' => $workout->id]);
    $block = Block::factory()->create(['section_id' => $section->id]);
    $strengthExercise = StrengthExercise::factory()->create([
        'target_sets' => 3,
        'target_reps_min' => 8,
        'target_reps_max' => 12,
        'target_weight' => 80.00,
        'target_rpe' => 7.5,
        'rest_after' => 90,
    ]);
    BlockExercise::factory()->create([
        'block_id' => $block->id,
        'name' => 'Squat',
        'exerciseable_type' => 'strength_exercise',
        'exerciseable_id' => $strengthExercise->id,
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->assertSee('Squat')
        ->assertSee('Strength')
        ->assertSee('3 sets of 8-12 reps')
        ->assertSee('80 kg')
        ->assertSee('RPE 7.5 (Hard)')
        ->assertSee('1min 30s between sets');
});

it('renders cardio exercise with labeled details', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->create(['user_id' => $user->id]);
    $section = Section::factory()->create(['workout_id' => $workout->id]);
    $block = Block::factory()->distanceDuration()->create(['section_id' => $section->id]);
    $cardioExercise = CardioExercise::factory()->create([
        'target_duration' => 1800,
        'target_distance' => 5000,
        'target_heart_rate_zone' => 3,
    ]);
    BlockExercise::factory()->create([
        'block_id' => $block->id,
        'name' => 'Easy Run',
        'exerciseable_type' => 'cardio_exercise',
        'exerciseable_id' => $cardioExercise->id,
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->assertSee('Easy Run')
        ->assertSee('Cardio')
        ->assertSee('30min')
        ->assertSee('5 km')
        ->assertSee('Zone 3');
});

it('renders duration exercise with labeled details', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->create(['user_id' => $user->id]);
    $section = Section::factory()->create(['workout_id' => $workout->id]);
    $block = Block::factory()->create(['section_id' => $section->id]);
    $durationExercise = DurationExercise::factory()->create([
        'target_duration' => 300,
        'target_rpe' => 3.0,
    ]);
    BlockExercise::factory()->create([
        'block_id' => $block->id,
        'name' => 'Static Stretch',
        'exerciseable_type' => 'duration_exercise',
        'exerciseable_id' => $durationExercise->id,
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->assertSee('Static Stretch')
        ->assertSee('Duration')
        ->assertSee('5min')
        ->assertSee('RPE 3 (Easy)');
});

it('renders block type badge and metadata tokens', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->create(['user_id' => $user->id]);
    $section = Section::factory()->create(['workout_id' => $workout->id]);
    Block::factory()->circuit()->create([
        'section_id' => $section->id,
        'rounds' => 3,
        'rest_between_exercises' => 30,
        'rest_between_rounds' => 60,
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->assertSee('Circuit')
        ->assertSee('3 rounds')
        ->assertSee('1min rest between rounds')
        ->assertSee('30s rest between exercises');
});

it('renders rest blocks without exercises', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->create(['user_id' => $user->id]);
    $section = Section::factory()->create(['workout_id' => $workout->id]);
    $restBlock = Block::factory()->rest()->create(['section_id' => $section->id]);

    $strengthExercise = StrengthExercise::factory()->create();
    BlockExercise::factory()->create([
        'block_id' => $restBlock->id,
        'name' => 'Should Not Appear',
        'exerciseable_type' => 'strength_exercise',
        'exerciseable_id' => $strengthExercise->id,
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->assertSee('Rest')
        ->assertDontSee('Should Not Appear');
});

it('renders interval block metadata', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->create(['user_id' => $user->id]);
    $section = Section::factory()->create(['workout_id' => $workout->id]);
    Block::factory()->interval()->create([
        'section_id' => $section->id,
        'rounds' => 8,
        'work_interval' => 30,
        'rest_interval' => 15,
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->assertSee('Interval')
        ->assertSee('8 rounds')
        ->assertSee('30s on / 15s off');
});

it('renders amrap block with time cap', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->create(['user_id' => $user->id]);
    $section = Section::factory()->create(['workout_id' => $workout->id]);
    Block::factory()->amrap()->create([
        'section_id' => $section->id,
        'time_cap' => 900,
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->assertSee('AMRAP')
        ->assertSee('Time cap: 15min');
});

it('shows exercise Do and Effort labels', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->create(['user_id' => $user->id]);
    $section = Section::factory()->create(['workout_id' => $workout->id]);
    $block = Block::factory()->create(['section_id' => $section->id]);
    $strengthExercise = StrengthExercise::factory()->create([
        'target_sets' => 4,
        'target_reps_max' => 6,
        'target_rpe' => 8.0,
    ]);
    BlockExercise::factory()->create([
        'block_id' => $block->id,
        'name' => 'Deadlift',
        'exerciseable_type' => 'strength_exercise',
        'exerciseable_id' => $strengthExercise->id,
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->assertSeeInOrder(['Do', '4 sets of 6 reps'])
        ->assertSeeInOrder(['Effort', 'RPE 8 (Hard)']);
});

it('renders exercise name as clickable when linked to exercise library', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->create(['user_id' => $user->id]);
    $section = Section::factory()->create(['workout_id' => $workout->id]);
    $block = Block::factory()->create(['section_id' => $section->id]);
    $exercise = Exercise::factory()->create();
    $strengthExercise = StrengthExercise::factory()->create();
    BlockExercise::factory()->create([
        'block_id' => $block->id,
        'name' => 'Linked Exercise',
        'exercise_id' => $exercise->id,
        'exerciseable_type' => 'strength_exercise',
        'exerciseable_id' => $strengthExercise->id,
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->assertSee('Linked Exercise')
        ->assertSeeHtml("exerciseId: {$exercise->id}");
});

it('renders exercise name as plain text when not linked to exercise library', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->create(['user_id' => $user->id]);
    $section = Section::factory()->create(['workout_id' => $workout->id]);
    $block = Block::factory()->create(['section_id' => $section->id]);
    $strengthExercise = StrengthExercise::factory()->create();
    BlockExercise::factory()->create([
        'block_id' => $block->id,
        'name' => 'Custom Exercise',
        'exercise_id' => null,
        'exerciseable_type' => 'strength_exercise',
        'exerciseable_id' => $strengthExercise->id,
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->assertSee('Custom Exercise')
        ->assertDontSeeHtml('exerciseId:');
});
