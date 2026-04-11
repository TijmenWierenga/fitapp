<?php

use App\Actions\Garmin\WorkoutFitMapper;
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
use App\Support\Fit\Decode\FitDecoder;
use App\Support\Fit\FitEncoder;
use App\Support\Fit\FitMessage;

function roundTripMessages(Workout $workout): array
{
    $mapper = new WorkoutFitMapper;
    $messages = $mapper->map($workout);
    $binary = (new FitEncoder)->encode($messages);
    $decoded = (new FitDecoder)->decode($binary);

    return $decoded;
}

function decodedSteps(array $messages): array
{
    return array_values(array_filter(
        $messages,
        fn (FitMessage $m): bool => $m->globalMessageNumber === 27,
    ));
}

function decodedExerciseTitles(array $messages): array
{
    return array_values(array_filter(
        $messages,
        fn (FitMessage $m): bool => $m->globalMessageNumber === 264,
    ));
}

function fieldValue(FitMessage $message, int $fieldNumber): mixed
{
    foreach ($message->fields as $field) {
        if ($field->fieldNumber === $fieldNumber) {
            return $field->value;
        }
    }

    return null;
}

it('round-trips a strength workout preserving sport and exercise titles', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    $workout = Workout::factory()->for($user)->create([
        'name' => 'Strength Day',
        'activity' => Activity::Strength,
    ]);

    $section = Section::factory()->for($workout)->create(['name' => 'Main', 'order' => 0]);
    $block = Block::factory()->for($section)->create(['block_type' => 'straight_sets', 'order' => 0]);

    $exercise = Exercise::factory()->withGarminMapping(GarminExerciseCategory::BenchPress, 3)->create();
    $strength = StrengthExercise::factory()->create(['target_sets' => 3, 'target_reps_max' => 10, 'rest_after' => 90]);
    BlockExercise::factory()->create([
        'block_id' => $block->id,
        'exercise_id' => $exercise->id,
        'name' => 'Bench Press',
        'order' => 0,
        'exerciseable_type' => 'strength_exercise',
        'exerciseable_id' => $strength->id,
    ]);

    $decoded = roundTripMessages($workout);

    // File ID present
    expect($decoded[0]->globalMessageNumber)->toBe(0);

    // Workout message preserves name and sport
    $workoutMsg = $decoded[1];
    expect(fieldValue($workoutMsg, 8))->toBe('Strength Day')
        ->and(fieldValue($workoutMsg, 4))->toBe(10) // sport = training
        ->and(fieldValue($workoutMsg, 11))->toBe(20); // subSport = strength_training

    // Steps survive the round-trip
    $steps = decodedSteps($decoded);
    expect($steps)->not->toBeEmpty();

    // Exercise title survives
    $titles = decodedExerciseTitles($decoded);
    expect($titles)->toHaveCount(1);
    expect(fieldValue($titles[0], 0))->toBe(GarminExerciseCategory::BenchPress->value); // category
    expect(fieldValue($titles[0], 1))->toBe(3); // exercise name
});

it('round-trips a cardio workout with distance step', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    $workout = Workout::factory()->for($user)->create([
        'name' => 'Morning Run',
        'activity' => Activity::Run,
    ]);

    $section = Section::factory()->for($workout)->create(['name' => 'Main', 'order' => 0]);
    $block = Block::factory()->distanceDuration()->for($section)->create(['order' => 0]);

    $cardio = CardioExercise::factory()->create(['target_distance' => 5000, 'target_heart_rate_zone' => 3]);
    BlockExercise::factory()->create([
        'block_id' => $block->id,
        'name' => '5k Run',
        'order' => 0,
        'exerciseable_type' => 'cardio_exercise',
        'exerciseable_id' => $cardio->id,
    ]);

    $decoded = roundTripMessages($workout);

    // Workout message preserves running sport
    $workoutMsg = $decoded[1];
    expect(fieldValue($workoutMsg, 8))->toBe('Morning Run')
        ->and(fieldValue($workoutMsg, 4))->toBe(1) // sport = running
        ->and(fieldValue($workoutMsg, 11))->toBe(0); // subSport = generic

    // Distance step present with correct value
    $steps = decodedSteps($decoded);
    expect($steps)->toHaveCount(1);

    // durationType = 1 (DISTANCE), durationValue = 500000 (5000m * 100 = cm)
    expect(fieldValue($steps[0], 1))->toBe(1);
    expect(fieldValue($steps[0], 2))->toBe(500000);

    // targetType = 1 (HEART_RATE), targetValue = 3 (zone 3)
    expect(fieldValue($steps[0], 3))->toBe(1);
    expect(fieldValue($steps[0], 4))->toBe(3);
});

it('round-trips a mixed workout with warmup and cooldown intensities', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    $workout = Workout::factory()->for($user)->create([
        'name' => 'Full Session',
        'activity' => Activity::Strength,
    ]);

    // Warmup section
    $warmup = Section::factory()->for($workout)->create(['name' => 'Warm-up', 'order' => 0]);
    $warmBlock = Block::factory()->for($warmup)->create(['block_type' => 'straight_sets', 'order' => 0]);
    $warmDuration = DurationExercise::factory()->create(['target_duration' => 300]);
    BlockExercise::factory()->create([
        'block_id' => $warmBlock->id,
        'name' => 'Jump Rope',
        'order' => 0,
        'exerciseable_type' => 'duration_exercise',
        'exerciseable_id' => $warmDuration->id,
    ]);

    // Main section with strength
    $main = Section::factory()->for($workout)->create(['name' => 'Main', 'order' => 1]);
    $mainBlock = Block::factory()->for($main)->create(['block_type' => 'straight_sets', 'order' => 0]);
    $exercise = Exercise::factory()->withGarminMapping(GarminExerciseCategory::Squat, 5)->create();
    $strength = StrengthExercise::factory()->create(['target_sets' => 4, 'target_reps_max' => 8]);
    BlockExercise::factory()->create([
        'block_id' => $mainBlock->id,
        'exercise_id' => $exercise->id,
        'name' => 'Squat',
        'order' => 0,
        'exerciseable_type' => 'strength_exercise',
        'exerciseable_id' => $strength->id,
    ]);

    // Cooldown section
    $cooldown = Section::factory()->for($workout)->create(['name' => 'Cooldown', 'order' => 2]);
    $coolBlock = Block::factory()->for($cooldown)->create(['block_type' => 'straight_sets', 'order' => 0]);
    $coolDuration = DurationExercise::factory()->create(['target_duration' => 180]);
    BlockExercise::factory()->create([
        'block_id' => $coolBlock->id,
        'name' => 'Stretch',
        'order' => 0,
        'exerciseable_type' => 'duration_exercise',
        'exerciseable_id' => $coolDuration->id,
    ]);

    $decoded = roundTripMessages($workout);

    $steps = decodedSteps($decoded);

    // Warmup step: intensity = 2 (WARMUP), duration = 300000ms
    expect(fieldValue($steps[0], 7))->toBe(2)
        ->and(fieldValue($steps[0], 0))->toBe('Jump Rope')
        ->and(fieldValue($steps[0], 2))->toBe(300000);

    // Main step: intensity = 0 (ACTIVE)
    expect(fieldValue($steps[1], 7))->toBe(0)
        ->and(fieldValue($steps[1], 0))->toBe('Squat');

    // Repeat step for 4 sets
    expect(fieldValue($steps[2], 1))->toBe(6); // durationType = REPEAT

    // Cooldown step: intensity = 3 (COOLDOWN), duration = 180000ms
    expect(fieldValue($steps[3], 7))->toBe(3)
        ->and(fieldValue($steps[3], 0))->toBe('Stretch')
        ->and(fieldValue($steps[3], 2))->toBe(180000);

    // Exercise titles: only Squat has Garmin mapping
    $titles = decodedExerciseTitles($decoded);
    expect($titles)->toHaveCount(1);
    expect(fieldValue($titles[0], 0))->toBe(GarminExerciseCategory::Squat->value);
});

it('round-trips interval block with work and rest steps', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    $workout = Workout::factory()->for($user)->create([
        'name' => 'Intervals',
        'activity' => Activity::Run,
    ]);

    $section = Section::factory()->for($workout)->create(['name' => 'Main', 'order' => 0]);
    $block = Block::factory()->interval()->for($section)->create([
        'order' => 0,
        'rounds' => 6,
        'work_interval' => 30,
        'rest_interval' => 15,
    ]);

    $cardio = CardioExercise::factory()->create();
    BlockExercise::factory()->create([
        'block_id' => $block->id,
        'name' => 'Sprint',
        'order' => 0,
        'exerciseable_type' => 'cardio_exercise',
        'exerciseable_id' => $cardio->id,
    ]);

    $decoded = roundTripMessages($workout);
    $steps = decodedSteps($decoded);

    // Work step: 30s = 30000ms
    expect(fieldValue($steps[0], 0))->toBe('Sprint')
        ->and(fieldValue($steps[0], 1))->toBe(0) // TIME
        ->and(fieldValue($steps[0], 2))->toBe(30000);

    // Rest step: 15s = 15000ms
    expect(fieldValue($steps[1], 0))->toBe('Rest')
        ->and(fieldValue($steps[1], 1))->toBe(0) // TIME
        ->and(fieldValue($steps[1], 2))->toBe(15000)
        ->and(fieldValue($steps[1], 7))->toBe(1); // REST intensity

    // Repeat step: 6 rounds
    expect(fieldValue($steps[2], 1))->toBe(6) // REPEAT
        ->and(fieldValue($steps[2], 4))->toBe(6); // count
});
