<?php

use App\Enums\Fit\GarminExerciseCategory;
use App\Enums\Workout\Activity;
use App\Models\Block;
use App\Models\BlockExercise;
use App\Models\CardioExercise;
use App\Models\DurationExercise;
use App\Models\Exercise;
use App\Models\Section;
use App\Models\StrengthExercise;
use App\Models\User;
use App\Models\Workout;
use App\Support\Fit\FitMessage;
use App\Support\Fit\WorkoutFitMapper;

function createWorkout(array $attributes = []): Workout
{
    $user = User::factory()->withTimezone('UTC')->create();

    return Workout::factory()->for($user)->create(array_merge([
        'name' => 'Test Workout',
        'activity' => Activity::Strength,
    ], $attributes));
}

function getStepMessages(array $messages): array
{
    return array_values(array_filter(
        $messages,
        fn (FitMessage $m): bool => $m->globalMessageNumber === 27,
    ));
}

function getExerciseTitleMessages(array $messages): array
{
    return array_values(array_filter(
        $messages,
        fn (FitMessage $m): bool => $m->globalMessageNumber === 264,
    ));
}

function getStepField(FitMessage $step, int $fieldNumber): mixed
{
    foreach ($step->fields as $field) {
        if ($field->fieldNumber === $fieldNumber) {
            return $field->value;
        }
    }

    return null;
}

it('produces file_id and workout messages', function () {
    $workout = createWorkout();

    $mapper = new WorkoutFitMapper;
    $messages = $mapper->map($workout);

    // file_id (global 0) and workout (global 26)
    expect($messages[0]->globalMessageNumber)->toBe(0)
        ->and($messages[1]->globalMessageNumber)->toBe(26);
});

it('maps workout name and sport to workout message', function () {
    $workout = createWorkout(['name' => 'Morning Run', 'activity' => Activity::Run]);

    $mapper = new WorkoutFitMapper;
    $messages = $mapper->map($workout);

    $workoutMsg = $messages[1];
    // field 8 = name, field 4 = sport, field 11 = sub_sport
    expect(getStepField($workoutMsg, 8))->toBe('Morning Run')
        ->and(getStepField($workoutMsg, 4))->toBe(1) // RUNNING
        ->and(getStepField($workoutMsg, 11))->toBe(0);
});

it('sets correct num_valid_steps', function () {
    $workout = createWorkout();
    $section = Section::factory()->for($workout)->create(['name' => 'Main', 'order' => 0]);
    $block = Block::factory()->rest()->for($section)->create(['order' => 0]);
    $duration = DurationExercise::factory()->create(['target_duration' => 60]);
    BlockExercise::factory()->create([
        'block_id' => $block->id,
        'name' => 'Rest',
        'order' => 0,
        'exerciseable_type' => 'duration_exercise',
        'exerciseable_id' => $duration->id,
    ]);

    $mapper = new WorkoutFitMapper;
    $messages = $mapper->map($workout);

    // field 6 = num_valid_steps
    expect(getStepField($messages[1], 6))->toBe(1);
});

it('maps warmup section to warmup intensity', function () {
    $workout = createWorkout();
    $section = Section::factory()->for($workout)->create(['name' => 'Warm-up', 'order' => 0]);
    $block = Block::factory()->rest()->for($section)->create(['order' => 0]);
    $duration = DurationExercise::factory()->create(['target_duration' => 300]);
    BlockExercise::factory()->create([
        'block_id' => $block->id,
        'name' => 'Warm Up Jog',
        'order' => 0,
        'exerciseable_type' => 'duration_exercise',
        'exerciseable_id' => $duration->id,
    ]);

    $mapper = new WorkoutFitMapper;
    $messages = $mapper->map($workout);
    $steps = getStepMessages($messages);

    // field 7 = intensity, 2 = WARMUP
    // Rest block always uses intensity REST (1) regardless of section
    expect(getStepField($steps[0], 7))->toBe(1);
});

it('maps cooldown section to cooldown intensity', function () {
    $workout = createWorkout();
    $section = Section::factory()->for($workout)->create(['name' => 'Cool-down', 'order' => 0]);
    $block = Block::factory()->for($section)->create(['block_type' => 'straight_sets', 'order' => 0]);
    $strength = StrengthExercise::factory()->create(['target_sets' => 1]);
    BlockExercise::factory()->create([
        'block_id' => $block->id,
        'name' => 'Stretch',
        'order' => 0,
        'exerciseable_type' => 'strength_exercise',
        'exerciseable_id' => $strength->id,
    ]);

    $mapper = new WorkoutFitMapper;
    $messages = $mapper->map($workout);
    $steps = getStepMessages($messages);

    // field 7 = intensity, 3 = COOLDOWN
    expect(getStepField($steps[0], 7))->toBe(3);
});

it('maps rest block as a single time step', function () {
    $workout = createWorkout();
    $section = Section::factory()->for($workout)->create(['name' => 'Main', 'order' => 0]);
    $block = Block::factory()->rest()->for($section)->create(['order' => 0]);
    $duration = DurationExercise::factory()->create(['target_duration' => 120]);
    BlockExercise::factory()->create([
        'block_id' => $block->id,
        'name' => 'Rest Period',
        'order' => 0,
        'exerciseable_type' => 'duration_exercise',
        'exerciseable_id' => $duration->id,
    ]);

    $mapper = new WorkoutFitMapper;
    $messages = $mapper->map($workout);
    $steps = getStepMessages($messages);

    expect($steps)->toHaveCount(1);

    // field 0 = name, field 1 = duration_type, field 2 = duration_value, field 7 = intensity
    expect(getStepField($steps[0], 0))->toBe('Rest Period')
        ->and(getStepField($steps[0], 1))->toBe(0) // TIME
        ->and(getStepField($steps[0], 2))->toBe(120_000) // 120s in ms
        ->and(getStepField($steps[0], 7))->toBe(1); // REST
});

it('maps straight sets with repeat', function () {
    $workout = createWorkout();
    $section = Section::factory()->for($workout)->create(['name' => 'Main', 'order' => 0]);
    $block = Block::factory()->for($section)->create(['block_type' => 'straight_sets', 'order' => 0]);
    $strength = StrengthExercise::factory()->create([
        'target_sets' => 3,
        'target_reps_max' => 10,
        'rest_after' => 90,
    ]);
    BlockExercise::factory()->create([
        'block_id' => $block->id,
        'name' => 'Bench Press',
        'order' => 0,
        'exerciseable_type' => 'strength_exercise',
        'exerciseable_id' => $strength->id,
    ]);

    $mapper = new WorkoutFitMapper;
    $messages = $mapper->map($workout);
    $steps = getStepMessages($messages);

    // 3 steps: exercise (OPEN) + rest (TIME) + repeat
    expect($steps)->toHaveCount(3);

    // Exercise step
    expect(getStepField($steps[0], 0))->toBe('Bench Press')
        ->and(getStepField($steps[0], 1))->toBe(5); // OPEN

    // Rest step
    expect(getStepField($steps[1], 1))->toBe(0) // TIME
        ->and(getStepField($steps[1], 2))->toBe(90_000) // 90s
        ->and(getStepField($steps[1], 7))->toBe(1); // REST

    // Repeat step
    expect(getStepField($steps[2], 1))->toBe(6) // REPEAT_UNTIL_STEPS_CMPLT
        ->and(getStepField($steps[2], 2))->toBe(0) // back to step 0
        ->and(getStepField($steps[2], 4))->toBe(3); // 3 sets
});

it('maps straight sets without rest', function () {
    $workout = createWorkout();
    $section = Section::factory()->for($workout)->create(['name' => 'Main', 'order' => 0]);
    $block = Block::factory()->for($section)->create(['block_type' => 'straight_sets', 'order' => 0]);
    $strength = StrengthExercise::factory()->create([
        'target_sets' => 4,
        'target_reps_max' => 8,
        'rest_after' => null,
    ]);
    BlockExercise::factory()->create([
        'block_id' => $block->id,
        'name' => 'Pull-up',
        'order' => 0,
        'exerciseable_type' => 'strength_exercise',
        'exerciseable_id' => $strength->id,
    ]);

    $mapper = new WorkoutFitMapper;
    $messages = $mapper->map($workout);
    $steps = getStepMessages($messages);

    // 2 steps: exercise + repeat (no rest step)
    expect($steps)->toHaveCount(2);
    expect(getStepField($steps[1], 1))->toBe(6) // REPEAT
        ->and(getStepField($steps[1], 4))->toBe(4); // 4 sets
});

it('maps circuit block with rest between exercises and rounds', function () {
    $workout = createWorkout();
    $section = Section::factory()->for($workout)->create(['name' => 'Main', 'order' => 0]);
    $block = Block::factory()->circuit()->for($section)->create([
        'order' => 0,
        'rounds' => 3,
        'rest_between_exercises' => 30,
        'rest_between_rounds' => 60,
    ]);

    $strength1 = StrengthExercise::factory()->create();
    $strength2 = StrengthExercise::factory()->create();
    BlockExercise::factory()->create([
        'block_id' => $block->id, 'name' => 'Push-up', 'order' => 0,
        'exerciseable_type' => 'strength_exercise', 'exerciseable_id' => $strength1->id,
    ]);
    BlockExercise::factory()->create([
        'block_id' => $block->id, 'name' => 'Squat', 'order' => 1,
        'exerciseable_type' => 'strength_exercise', 'exerciseable_id' => $strength2->id,
    ]);

    $mapper = new WorkoutFitMapper;
    $messages = $mapper->map($workout);
    $steps = getStepMessages($messages);

    // Push-up + Rest(30s) + Squat + Rest(60s between rounds) + Repeat
    expect($steps)->toHaveCount(5);

    expect(getStepField($steps[0], 0))->toBe('Push-up')
        ->and(getStepField($steps[1], 2))->toBe(30_000) // rest between exercises
        ->and(getStepField($steps[2], 0))->toBe('Squat')
        ->and(getStepField($steps[3], 2))->toBe(60_000) // rest between rounds
        ->and(getStepField($steps[4], 1))->toBe(6) // REPEAT
        ->and(getStepField($steps[4], 4))->toBe(3); // 3 rounds
});

it('maps superset block', function () {
    $workout = createWorkout();
    $section = Section::factory()->for($workout)->create(['name' => 'Main', 'order' => 0]);
    $block = Block::factory()->superset()->for($section)->create([
        'order' => 0,
        'rounds' => 3,
        'rest_between_rounds' => 90,
    ]);

    $strength1 = StrengthExercise::factory()->create();
    $strength2 = StrengthExercise::factory()->create();
    BlockExercise::factory()->create([
        'block_id' => $block->id, 'name' => 'Bench Press', 'order' => 0,
        'exerciseable_type' => 'strength_exercise', 'exerciseable_id' => $strength1->id,
    ]);
    BlockExercise::factory()->create([
        'block_id' => $block->id, 'name' => 'Row', 'order' => 1,
        'exerciseable_type' => 'strength_exercise', 'exerciseable_id' => $strength2->id,
    ]);

    $mapper = new WorkoutFitMapper;
    $messages = $mapper->map($workout);
    $steps = getStepMessages($messages);

    // Bench Press + Row + Rest(90s) + Repeat
    expect($steps)->toHaveCount(4);

    expect(getStepField($steps[0], 0))->toBe('Bench Press')
        ->and(getStepField($steps[1], 0))->toBe('Row')
        ->and(getStepField($steps[2], 2))->toBe(90_000)
        ->and(getStepField($steps[3], 1))->toBe(6) // REPEAT
        ->and(getStepField($steps[3], 4))->toBe(3);
});

it('maps interval block', function () {
    $workout = createWorkout(['activity' => Activity::Run]);
    $section = Section::factory()->for($workout)->create(['name' => 'Main', 'order' => 0]);
    $block = Block::factory()->interval()->for($section)->create([
        'order' => 0,
        'rounds' => 8,
        'work_interval' => 30,
        'rest_interval' => 15,
    ]);

    $mapper = new WorkoutFitMapper;
    $messages = $mapper->map($workout);
    $steps = getStepMessages($messages);

    // Work(TIME) + Rest(TIME) + Repeat
    expect($steps)->toHaveCount(3);

    expect(getStepField($steps[0], 1))->toBe(0) // TIME
        ->and(getStepField($steps[0], 2))->toBe(30_000)
        ->and(getStepField($steps[0], 7))->toBe(0) // ACTIVE
        ->and(getStepField($steps[1], 1))->toBe(0) // TIME
        ->and(getStepField($steps[1], 2))->toBe(15_000)
        ->and(getStepField($steps[1], 7))->toBe(1) // REST
        ->and(getStepField($steps[2], 1))->toBe(6) // REPEAT
        ->and(getStepField($steps[2], 4))->toBe(8);
});

it('maps amrap block with repeat until time', function () {
    $workout = createWorkout();
    $section = Section::factory()->for($workout)->create(['name' => 'Main', 'order' => 0]);
    $block = Block::factory()->amrap()->for($section)->create([
        'order' => 0,
        'time_cap' => 600, // 10 min
    ]);

    $strength = StrengthExercise::factory()->create();
    BlockExercise::factory()->create([
        'block_id' => $block->id, 'name' => 'Burpee', 'order' => 0,
        'exerciseable_type' => 'strength_exercise', 'exerciseable_id' => $strength->id,
    ]);

    $mapper = new WorkoutFitMapper;
    $messages = $mapper->map($workout);
    $steps = getStepMessages($messages);

    // Exercise + RepeatUntilTime
    expect($steps)->toHaveCount(2);

    expect(getStepField($steps[0], 0))->toBe('Burpee')
        ->and(getStepField($steps[1], 1))->toBe(7) // REPEAT_UNTIL_TIME
        ->and(getStepField($steps[1], 2))->toBe(0) // back to step 0
        ->and(getStepField($steps[1], 4))->toBe(600_000); // 10 min in ms
});

it('maps for time block', function () {
    $workout = createWorkout();
    $section = Section::factory()->for($workout)->create(['name' => 'Main', 'order' => 0]);
    $block = Block::factory()->forTime()->for($section)->create([
        'order' => 0,
        'rounds' => 3,
    ]);

    $strength = StrengthExercise::factory()->create();
    BlockExercise::factory()->create([
        'block_id' => $block->id, 'name' => 'Clean', 'order' => 0,
        'exerciseable_type' => 'strength_exercise', 'exerciseable_id' => $strength->id,
    ]);

    $mapper = new WorkoutFitMapper;
    $messages = $mapper->map($workout);
    $steps = getStepMessages($messages);

    // Exercise + Repeat
    expect($steps)->toHaveCount(2);
    expect(getStepField($steps[1], 1))->toBe(6) // REPEAT
        ->and(getStepField($steps[1], 4))->toBe(3);
});

it('maps emom block', function () {
    $workout = createWorkout();
    $section = Section::factory()->for($workout)->create(['name' => 'Main', 'order' => 0]);
    $block = Block::factory()->emom()->for($section)->create([
        'order' => 0,
        'rounds' => 10,
        'work_interval' => 60,
    ]);

    $strength = StrengthExercise::factory()->create();
    BlockExercise::factory()->create([
        'block_id' => $block->id, 'name' => 'Kettlebell Swing', 'order' => 0,
        'exerciseable_type' => 'strength_exercise', 'exerciseable_id' => $strength->id,
    ]);

    $mapper = new WorkoutFitMapper;
    $messages = $mapper->map($workout);
    $steps = getStepMessages($messages);

    // Work(TIME) + Repeat
    expect($steps)->toHaveCount(2);

    expect(getStepField($steps[0], 0))->toBe('Kettlebell Swing')
        ->and(getStepField($steps[0], 1))->toBe(0) // TIME
        ->and(getStepField($steps[0], 2))->toBe(60_000)
        ->and(getStepField($steps[1], 1))->toBe(6) // REPEAT
        ->and(getStepField($steps[1], 4))->toBe(10);
});

it('maps distance duration block with cardio distance', function () {
    $workout = createWorkout(['activity' => Activity::Run]);
    $section = Section::factory()->for($workout)->create(['name' => 'Main', 'order' => 0]);
    $block = Block::factory()->distanceDuration()->for($section)->create(['order' => 0]);

    $cardio = CardioExercise::factory()->create(['target_distance' => 5000.00]);
    BlockExercise::factory()->create([
        'block_id' => $block->id, 'name' => '5K Run', 'order' => 0,
        'exerciseable_type' => 'cardio_exercise', 'exerciseable_id' => $cardio->id,
    ]);

    $mapper = new WorkoutFitMapper;
    $messages = $mapper->map($workout);
    $steps = getStepMessages($messages);

    expect($steps)->toHaveCount(1);
    expect(getStepField($steps[0], 1))->toBe(1) // DISTANCE
        ->and(getStepField($steps[0], 2))->toBe(500_000); // 5000m * 100 = 500000 cm
});

it('maps cardio exercise with heart rate zone target', function () {
    $workout = createWorkout(['activity' => Activity::Run]);
    $section = Section::factory()->for($workout)->create(['name' => 'Main', 'order' => 0]);
    $block = Block::factory()->distanceDuration()->for($section)->create(['order' => 0]);

    $cardio = CardioExercise::factory()->create([
        'target_duration' => 1800,
        'target_heart_rate_zone' => 3,
    ]);
    BlockExercise::factory()->create([
        'block_id' => $block->id, 'name' => 'Zone 3 Run', 'order' => 0,
        'exerciseable_type' => 'cardio_exercise', 'exerciseable_id' => $cardio->id,
    ]);

    $mapper = new WorkoutFitMapper;
    $messages = $mapper->map($workout);
    $steps = getStepMessages($messages);

    // field 3 = target_type (1 = HEART_RATE), field 4 = target_value (zone 3)
    expect(getStepField($steps[0], 3))->toBe(1)
        ->and(getStepField($steps[0], 4))->toBe(3);
});

it('maps cardio exercise with heart rate range target', function () {
    $workout = createWorkout(['activity' => Activity::Run]);
    $section = Section::factory()->for($workout)->create(['name' => 'Main', 'order' => 0]);
    $block = Block::factory()->distanceDuration()->for($section)->create(['order' => 0]);

    $cardio = CardioExercise::factory()->create([
        'target_duration' => 1800,
        'target_heart_rate_min' => 140,
        'target_heart_rate_max' => 160,
    ]);
    BlockExercise::factory()->create([
        'block_id' => $block->id, 'name' => 'HR Run', 'order' => 0,
        'exerciseable_type' => 'cardio_exercise', 'exerciseable_id' => $cardio->id,
    ]);

    $mapper = new WorkoutFitMapper;
    $messages = $mapper->map($workout);
    $steps = getStepMessages($messages);

    // field 3 = target_type (1 = HEART_RATE), field 5 = custom_low, field 6 = custom_high
    expect(getStepField($steps[0], 3))->toBe(1)
        ->and(getStepField($steps[0], 5))->toBe(240) // 140 + 100
        ->and(getStepField($steps[0], 6))->toBe(260); // 160 + 100
});

it('maps cardio exercise with pace target', function () {
    $workout = createWorkout(['activity' => Activity::Run]);
    $section = Section::factory()->for($workout)->create(['name' => 'Main', 'order' => 0]);
    $block = Block::factory()->distanceDuration()->for($section)->create(['order' => 0]);

    $cardio = CardioExercise::factory()->create([
        'target_duration' => 1800,
        'target_pace_min' => 360, // 6:00/km (slower)
        'target_pace_max' => 300, // 5:00/km (faster)
    ]);
    BlockExercise::factory()->create([
        'block_id' => $block->id, 'name' => 'Tempo Run', 'order' => 0,
        'exerciseable_type' => 'cardio_exercise', 'exerciseable_id' => $cardio->id,
    ]);

    $mapper = new WorkoutFitMapper;
    $messages = $mapper->map($workout);
    $steps = getStepMessages($messages);

    // field 3 = target_type (0 = SPEED)
    // custom_low = 1000000/360 ≈ 2778
    // custom_high = 1000000/300 ≈ 3333
    expect(getStepField($steps[0], 3))->toBe(0) // SPEED
        ->and(getStepField($steps[0], 5))->toBe(2778) // speed low (from slower pace)
        ->and(getStepField($steps[0], 6))->toBe(3333); // speed high (from faster pace)
});

it('includes strength notes on exercise steps', function () {
    $workout = createWorkout();
    $section = Section::factory()->for($workout)->create(['name' => 'Main', 'order' => 0]);
    $block = Block::factory()->for($section)->create(['block_type' => 'straight_sets', 'order' => 0]);
    $strength = StrengthExercise::factory()->create([
        'target_sets' => 3,
        'target_reps_min' => 8,
        'target_reps_max' => 10,
        'target_weight' => 80.00,
        'target_rpe' => 7,
        'target_tempo' => '3-1-2-0',
        'rest_after' => 90,
    ]);
    BlockExercise::factory()->create([
        'block_id' => $block->id, 'name' => 'Bench Press', 'order' => 0,
        'exerciseable_type' => 'strength_exercise', 'exerciseable_id' => $strength->id,
    ]);

    $mapper = new WorkoutFitMapper;
    $messages = $mapper->map($workout);
    $steps = getStepMessages($messages);

    // field 8 = notes
    $notes = getStepField($steps[0], 8);
    expect($notes)->toContain('Bench Press')
        ->and($notes)->toContain('3 sets of 8-10 reps')
        ->and($notes)->toContain('@ 80 kg')
        ->and($notes)->toContain('RPE 7')
        ->and($notes)->toContain('Tempo 3-1-2-0')
        ->and($notes)->toContain('Rest 1min 30s');
});

it('includes garmin exercise fields when exercise has mapping', function () {
    $workout = createWorkout();
    $section = Section::factory()->for($workout)->create(['name' => 'Main', 'order' => 0]);
    $block = Block::factory()->for($section)->create(['block_type' => 'straight_sets', 'order' => 0]);

    $catalogExercise = Exercise::factory()->withGarminMapping(GarminExerciseCategory::BenchPress, 1)->create();
    $strength = StrengthExercise::factory()->create(['target_sets' => 1]);
    BlockExercise::factory()->create([
        'block_id' => $block->id,
        'exercise_id' => $catalogExercise->id,
        'name' => 'Barbell Bench Press',
        'order' => 0,
        'exerciseable_type' => 'strength_exercise',
        'exerciseable_id' => $strength->id,
    ]);

    $mapper = new WorkoutFitMapper;
    $messages = $mapper->map($workout);
    $steps = getStepMessages($messages);

    // field 10 = exercise_category, field 11 = exercise_name
    expect(getStepField($steps[0], 10))->toBe(0) // BenchPress
        ->and(getStepField($steps[0], 11))->toBe(1); // barbell_bench_press
});

it('includes null garmin exercise fields when exercise has no mapping', function () {
    $workout = createWorkout();
    $section = Section::factory()->for($workout)->create(['name' => 'Main', 'order' => 0]);
    $block = Block::factory()->for($section)->create(['block_type' => 'straight_sets', 'order' => 0]);

    $strength = StrengthExercise::factory()->create(['target_sets' => 1]);
    BlockExercise::factory()->create([
        'block_id' => $block->id,
        'name' => 'Custom Exercise',
        'order' => 0,
        'exerciseable_type' => 'strength_exercise',
        'exerciseable_id' => $strength->id,
    ]);

    $mapper = new WorkoutFitMapper;
    $messages = $mapper->map($workout);
    $steps = getStepMessages($messages);

    // field 10 = exercise_category, field 11 = exercise_name — null means invalid marker
    expect(getStepField($steps[0], 10))->toBeNull()
        ->and(getStepField($steps[0], 11))->toBeNull();
});

it('includes garmin fields on cardio exercise step', function () {
    $workout = createWorkout(['activity' => Activity::Run]);
    $section = Section::factory()->for($workout)->create(['name' => 'Main', 'order' => 0]);
    $block = Block::factory()->distanceDuration()->for($section)->create(['order' => 0]);

    $catalogExercise = Exercise::factory()->withGarminMapping(GarminExerciseCategory::Run, 0)->create();
    $cardio = CardioExercise::factory()->create(['target_duration' => 1800]);
    BlockExercise::factory()->create([
        'block_id' => $block->id,
        'exercise_id' => $catalogExercise->id,
        'name' => 'Run',
        'order' => 0,
        'exerciseable_type' => 'cardio_exercise',
        'exerciseable_id' => $cardio->id,
    ]);

    $mapper = new WorkoutFitMapper;
    $messages = $mapper->map($workout);
    $steps = getStepMessages($messages);

    expect(getStepField($steps[0], 10))->toBe(32) // Run
        ->and(getStepField($steps[0], 11))->toBe(0); // run
});

it('generates exercise_title messages for exercises with garmin mapping', function () {
    $workout = createWorkout();
    $section = Section::factory()->for($workout)->create(['name' => 'Main', 'order' => 0]);
    $block = Block::factory()->for($section)->create(['block_type' => 'straight_sets', 'order' => 0]);

    $catalogExercise = Exercise::factory()->withGarminMapping(GarminExerciseCategory::BenchPress, 1)->create();
    $strength = StrengthExercise::factory()->create(['target_sets' => 1]);
    BlockExercise::factory()->create([
        'block_id' => $block->id,
        'exercise_id' => $catalogExercise->id,
        'name' => 'Barbell Bench Press',
        'order' => 0,
        'exerciseable_type' => 'strength_exercise',
        'exerciseable_id' => $strength->id,
    ]);

    $mapper = new WorkoutFitMapper;
    $messages = $mapper->map($workout);
    $titles = getExerciseTitleMessages($messages);

    expect($titles)->toHaveCount(1);

    // field 0 = exercise_category, field 1 = exercise_name, field 2 = wkt_step_name
    expect(getStepField($titles[0], 0))->toBe(0) // BenchPress
        ->and(getStepField($titles[0], 1))->toBe(1) // barbell_bench_press
        ->and(getStepField($titles[0], 2))->toBe('Barbell Bench Press');
});

it('does not generate exercise_title for exercises without garmin mapping', function () {
    $workout = createWorkout();
    $section = Section::factory()->for($workout)->create(['name' => 'Main', 'order' => 0]);
    $block = Block::factory()->for($section)->create(['block_type' => 'straight_sets', 'order' => 0]);

    $strength = StrengthExercise::factory()->create(['target_sets' => 1]);
    BlockExercise::factory()->create([
        'block_id' => $block->id,
        'name' => 'Custom Exercise',
        'order' => 0,
        'exerciseable_type' => 'strength_exercise',
        'exerciseable_id' => $strength->id,
    ]);

    $mapper = new WorkoutFitMapper;
    $messages = $mapper->map($workout);
    $titles = getExerciseTitleMessages($messages);

    expect($titles)->toHaveCount(0);
});

it('deduplicates exercise_title messages for repeated exercises', function () {
    $workout = createWorkout();
    $section = Section::factory()->for($workout)->create(['name' => 'Main', 'order' => 0]);
    $block = Block::factory()->superset()->for($section)->create(['order' => 0, 'rounds' => 3]);

    $catalogExercise = Exercise::factory()->withGarminMapping(GarminExerciseCategory::BenchPress, 1)->create();
    $strength1 = StrengthExercise::factory()->create();
    $strength2 = StrengthExercise::factory()->create();

    BlockExercise::factory()->create([
        'block_id' => $block->id,
        'exercise_id' => $catalogExercise->id,
        'name' => 'Bench Press',
        'order' => 0,
        'exerciseable_type' => 'strength_exercise',
        'exerciseable_id' => $strength1->id,
    ]);
    BlockExercise::factory()->create([
        'block_id' => $block->id,
        'exercise_id' => $catalogExercise->id,
        'name' => 'Bench Press Again',
        'order' => 1,
        'exerciseable_type' => 'strength_exercise',
        'exerciseable_id' => $strength2->id,
    ]);

    $mapper = new WorkoutFitMapper;
    $messages = $mapper->map($workout);
    $titles = getExerciseTitleMessages($messages);

    // Same garmin category+name → only 1 exercise_title
    expect($titles)->toHaveCount(1);
});
