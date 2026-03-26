<?php

declare(strict_types=1);

use App\Actions\ImportGarminActivity;
use App\Enums\Fit\GarminExerciseCategory;
use App\Enums\Workout\Activity;
use App\Exceptions\FitParseException;
use App\Models\Exercise;
use App\Models\ExerciseSet;
use App\Models\User;
use App\Models\Workout;
use Tests\Support\FitActivityFixtureBuilder;

it('imports a new strength workout from FIT data', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    $benchPress = Exercise::factory()
        ->withGarminMapping(GarminExerciseCategory::BenchPress, 0)
        ->create(['name' => 'Bench Press']);

    $squat = Exercise::factory()
        ->withGarminMapping(GarminExerciseCategory::Squat, 0)
        ->create(['name' => 'Squat']);

    $row = Exercise::factory()
        ->withGarminMapping(GarminExerciseCategory::Row, 0)
        ->create(['name' => 'Row']);

    $fitData = (new FitActivityFixtureBuilder)
        ->withSession(sport: 10, subSport: 20, totalElapsedTime: 3600, totalCalories: 400)
        // Bench Press 3x10@80kg
        ->addSet(setType: 0, repetitions: 10, weight: 80.0, exerciseCategory: GarminExerciseCategory::BenchPress->value, exerciseName: 0)
        ->addSet(setType: 1, duration: 90) // rest
        ->addSet(setType: 0, repetitions: 10, weight: 80.0, exerciseCategory: GarminExerciseCategory::BenchPress->value, exerciseName: 0)
        ->addSet(setType: 1, duration: 90)
        ->addSet(setType: 0, repetitions: 10, weight: 80.0, exerciseCategory: GarminExerciseCategory::BenchPress->value, exerciseName: 0)
        // Squat 4x8@100kg
        ->addSet(setType: 0, repetitions: 8, weight: 100.0, exerciseCategory: GarminExerciseCategory::Squat->value, exerciseName: 0)
        ->addSet(setType: 1, duration: 120)
        ->addSet(setType: 0, repetitions: 8, weight: 100.0, exerciseCategory: GarminExerciseCategory::Squat->value, exerciseName: 0)
        ->addSet(setType: 1, duration: 120)
        ->addSet(setType: 0, repetitions: 8, weight: 100.0, exerciseCategory: GarminExerciseCategory::Squat->value, exerciseName: 0)
        ->addSet(setType: 1, duration: 120)
        ->addSet(setType: 0, repetitions: 8, weight: 100.0, exerciseCategory: GarminExerciseCategory::Squat->value, exerciseName: 0)
        // Row 3x12@60kg
        ->addSet(setType: 0, repetitions: 12, weight: 60.0, exerciseCategory: GarminExerciseCategory::Row->value, exerciseName: 0)
        ->addSet(setType: 1, duration: 90)
        ->addSet(setType: 0, repetitions: 12, weight: 60.0, exerciseCategory: GarminExerciseCategory::Row->value, exerciseName: 0)
        ->addSet(setType: 1, duration: 90)
        ->addSet(setType: 0, repetitions: 12, weight: 60.0, exerciseCategory: GarminExerciseCategory::Row->value, exerciseName: 0)
        ->addExerciseTitle(GarminExerciseCategory::BenchPress->value, 0, 'Bench Press')
        ->addExerciseTitle(GarminExerciseCategory::Squat->value, 0, 'Squat')
        ->addExerciseTitle(GarminExerciseCategory::Row->value, 0, 'Row')
        ->build();

    $action = app(ImportGarminActivity::class);
    $result = $action->execute($user, $fitData, rpe: 7, feeling: 4);

    // Workout created correctly
    expect($result->workout->activity)->toBe(Activity::Strength)
        ->and($result->workout->isCompleted())->toBeTrue()
        ->and($result->workout->total_duration)->toBe(3600)
        ->and($result->workout->total_calories)->toBe(400)
        ->and($result->workout->source)->toBe('garmin_fit')
        ->and($result->workout->rpe)->toBe(7)
        ->and($result->workout->feeling)->toBe(4);

    // Structure: 1 section, 3 blocks, 3 block_exercises
    $sections = $result->workout->sections;
    expect($sections)->toHaveCount(1);

    $blocks = $sections->first()->blocks;
    expect($blocks)->toHaveCount(3);

    // Exercise sets: 3+4+3 = 10
    $totalSets = ExerciseSet::count();
    expect($totalSets)->toBe(10);

    // Bench press sets
    $benchExercise = $blocks[0]->exercises->first();
    expect($benchExercise->exercise_id)->toBe($benchPress->id);

    $benchSets = ExerciseSet::where('block_exercise_id', $benchExercise->id)->orderBy('set_number')->get();
    expect($benchSets)->toHaveCount(3);
    expect($benchSets[0]->reps)->toBe(10)
        ->and((float) $benchSets[0]->weight)->toBe(80.0);

    // Matched exercises reported
    expect($result->matchedExercises)->toContain('Bench Press')
        ->and($result->matchedExercises)->toContain('Squat')
        ->and($result->matchedExercises)->toContain('Row');
});

it('imports a new cardio workout from FIT data', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    $fitData = (new FitActivityFixtureBuilder)
        ->withSession(sport: 1, subSport: 0, totalElapsedTime: 1800, totalDistance: 5000, avgHeartRate: 155, maxHeartRate: 178, totalCalories: 350)
        ->addLap(totalElapsedTime: 360, totalDistance: 1000, avgHeartRate: 150, maxHeartRate: 165, avgCadence: 85)
        ->addLap(totalElapsedTime: 350, totalDistance: 1000, avgHeartRate: 155, maxHeartRate: 170, avgCadence: 87)
        ->addLap(totalElapsedTime: 340, totalDistance: 1000, avgHeartRate: 158, maxHeartRate: 175, avgCadence: 86)
        ->addLap(totalElapsedTime: 380, totalDistance: 1000, avgHeartRate: 160, maxHeartRate: 178, avgCadence: 88)
        ->addLap(totalElapsedTime: 370, totalDistance: 1000, avgHeartRate: 157, maxHeartRate: 172, avgCadence: 84)
        ->build();

    $action = app(ImportGarminActivity::class);
    $result = $action->execute($user, $fitData, rpe: 6, feeling: 3);

    expect($result->workout->activity)->toBe(Activity::Run)
        ->and($result->workout->isCompleted())->toBeTrue()
        ->and($result->workout->total_distance)->toBe('5000.00')
        ->and($result->workout->total_duration)->toBe(1800)
        ->and($result->workout->avg_heart_rate)->toBe(155)
        ->and($result->workout->max_heart_rate)->toBe(178);

    // 5 exercise_sets (one per lap)
    $totalSets = ExerciseSet::count();
    expect($totalSets)->toBe(5);

    $firstSet = ExerciseSet::where('set_number', 1)->first();
    expect((float) $firstSet->distance)->toBe(1000.0)
        ->and($firstSet->duration)->toBe(360)
        ->and($firstSet->avg_heart_rate)->toBe(150)
        ->and($firstSet->max_heart_rate)->toBe(165)
        ->and($firstSet->avg_cadence)->toBe(85);
});

it('merges FIT data into a planned strength workout', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    $benchPress = Exercise::factory()
        ->withGarminMapping(GarminExerciseCategory::BenchPress, 0)
        ->create(['name' => 'Bench Press']);

    // Create a planned workout with bench press
    $createAction = app(\App\Actions\CreateStructuredWorkout::class);
    $workout = $createAction->execute(
        user: $user,
        name: 'Strength Day',
        activity: Activity::Strength,
        scheduledAt: \Carbon\CarbonImmutable::now(),
        notes: null,
        sections: collect([
            new \App\DataTransferObjects\Workout\SectionData(
                name: 'Main',
                order: 0,
                blocks: collect([
                    new \App\DataTransferObjects\Workout\BlockData(
                        blockType: \App\Enums\Workout\BlockType::StraightSets,
                        order: 0,
                        exercises: collect([
                            new \App\DataTransferObjects\Workout\ExerciseData(
                                name: 'Bench Press',
                                order: 0,
                                type: \App\Enums\Workout\ExerciseType::Strength,
                                exerciseable: new \App\DataTransferObjects\Workout\StrengthExerciseData(
                                    targetSets: 3,
                                    targetRepsMax: 10,
                                    targetWeight: 80.0,
                                ),
                                exerciseId: $benchPress->id,
                            ),
                        ]),
                    ),
                ]),
            ),
        ]),
    );

    $fitData = (new FitActivityFixtureBuilder)
        ->withSession(sport: 10, subSport: 20, totalElapsedTime: 1800, totalCalories: 200)
        ->addSet(setType: 0, repetitions: 10, weight: 80.0, exerciseCategory: GarminExerciseCategory::BenchPress->value, exerciseName: 0)
        ->addSet(setType: 1, duration: 90)
        ->addSet(setType: 0, repetitions: 10, weight: 82.5, exerciseCategory: GarminExerciseCategory::BenchPress->value, exerciseName: 0)
        ->addSet(setType: 1, duration: 90)
        ->addSet(setType: 0, repetitions: 8, weight: 85.0, exerciseCategory: GarminExerciseCategory::BenchPress->value, exerciseName: 0)
        ->addExerciseTitle(GarminExerciseCategory::BenchPress->value, 0, 'Bench Press')
        ->build();

    $action = app(ImportGarminActivity::class);
    $result = $action->execute($user, $fitData, existingWorkout: $workout, rpe: 7, feeling: 4);

    expect($result->workout->isCompleted())->toBeTrue()
        ->and($result->workout->source)->toBe('garmin_fit');

    // Sets linked to correct block exercise
    $blockExercise = $workout->fresh()->sections->first()->blocks->first()->exercises->first();
    $sets = ExerciseSet::where('block_exercise_id', $blockExercise->id)->orderBy('set_number')->get();
    expect($sets)->toHaveCount(3);
    expect($sets[0]->reps)->toBe(10)->and((float) $sets[0]->weight)->toBe(80.0);
    expect($sets[1]->reps)->toBe(10)->and((float) $sets[1]->weight)->toBe(82.5);
    expect($sets[2]->reps)->toBe(8)->and((float) $sets[2]->weight)->toBe(85.0);

    expect($result->matchedExercises)->toContain('Bench Press');
});

it('appends extra exercises when FIT has more exercises than planned', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    $benchPress = Exercise::factory()
        ->withGarminMapping(GarminExerciseCategory::BenchPress, 0)
        ->create(['name' => 'Bench Press']);

    $createAction = app(\App\Actions\CreateStructuredWorkout::class);
    $workout = $createAction->execute(
        user: $user,
        name: 'Strength Day',
        activity: Activity::Strength,
        scheduledAt: \Carbon\CarbonImmutable::now(),
        notes: null,
        sections: collect([
            new \App\DataTransferObjects\Workout\SectionData(
                name: 'Main',
                order: 0,
                blocks: collect([
                    new \App\DataTransferObjects\Workout\BlockData(
                        blockType: \App\Enums\Workout\BlockType::StraightSets,
                        order: 0,
                        exercises: collect([
                            new \App\DataTransferObjects\Workout\ExerciseData(
                                name: 'Bench Press',
                                order: 0,
                                type: \App\Enums\Workout\ExerciseType::Strength,
                                exerciseable: new \App\DataTransferObjects\Workout\StrengthExerciseData(targetSets: 3),
                                exerciseId: $benchPress->id,
                            ),
                        ]),
                    ),
                ]),
            ),
        ]),
    );

    $fitData = (new FitActivityFixtureBuilder)
        ->withSession(sport: 10, subSport: 20, totalElapsedTime: 2400)
        ->addSet(setType: 0, repetitions: 10, weight: 80.0, exerciseCategory: GarminExerciseCategory::BenchPress->value, exerciseName: 0)
        ->addSet(setType: 0, repetitions: 10, weight: 80.0, exerciseCategory: GarminExerciseCategory::BenchPress->value, exerciseName: 0)
        // Extra exercise not in plan
        ->addSet(setType: 0, repetitions: 12, weight: 60.0, exerciseCategory: GarminExerciseCategory::Curl->value, exerciseName: 0)
        ->addSet(setType: 0, repetitions: 12, weight: 60.0, exerciseCategory: GarminExerciseCategory::Curl->value, exerciseName: 0)
        ->addExerciseTitle(GarminExerciseCategory::BenchPress->value, 0, 'Bench Press')
        ->addExerciseTitle(GarminExerciseCategory::Curl->value, 0, 'Bicep Curl')
        ->build();

    $action = app(ImportGarminActivity::class);
    $result = $action->execute($user, $fitData, existingWorkout: $workout, rpe: 6, feeling: 3);

    expect($result->matchedExercises)->toContain('Bench Press');
    expect($result->warnings)->not->toBeEmpty();

    // Total exercise sets: 2 bench + 2 curl = 4
    expect(ExerciseSet::count())->toBe(4);
});

it('handles unrecognized exercises by creating them without exercise_id', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    $fitData = (new FitActivityFixtureBuilder)
        ->withSession(sport: 10, subSport: 20, totalElapsedTime: 1200)
        ->addSet(setType: 0, repetitions: 10, weight: 50.0, exerciseCategory: 9999, exerciseName: 9999)
        ->addSet(setType: 0, repetitions: 10, weight: 50.0, exerciseCategory: 9999, exerciseName: 9999)
        ->addExerciseTitle(9999, 9999, 'Mystery Exercise')
        ->build();

    $action = app(ImportGarminActivity::class);
    $result = $action->execute($user, $fitData, rpe: 5, feeling: 3);

    expect($result->workout->isCompleted())->toBeTrue();
    expect($result->unmatchedExercises)->toContain('Mystery Exercise');
    expect($result->warnings)->not->toBeEmpty();

    // Sets still created
    expect(ExerciseSet::count())->toBe(2);

    // Block exercise has no exercise_id
    $blockExercise = $result->workout->sections->first()->blocks->first()->exercises->first();
    expect($blockExercise->exercise_id)->toBeNull();
});

it('throws FitParseException for invalid file', function () {
    $user = User::factory()->create();
    $action = app(ImportGarminActivity::class);

    $action->execute($user, 'random garbage bytes');
})->throws(FitParseException::class);

it('throws FitParseException for wrong file type', function () {
    $user = User::factory()->create();

    // Build a workout file (type=5), not an activity (type=4)
    $encoder = new \App\Support\Fit\FitEncoder;
    $messages = [\App\Support\Fit\FitMessageFactory::fileId()]; // type = 5
    $data = $encoder->encode($messages);

    $action = app(ImportGarminActivity::class);
    $action->execute($user, $data);
})->throws(FitParseException::class, 'not an activity');

// --- Duplicate Detection ---

it('warns about possible duplicate when importing same activity twice', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    $fitData = (new FitActivityFixtureBuilder)
        ->withSession(sport: 1, subSport: 0, totalElapsedTime: 1800, totalDistance: 5000)
        ->addLap(totalElapsedTime: 900, totalDistance: 2500)
        ->addLap(totalElapsedTime: 900, totalDistance: 2500)
        ->build();

    $action = app(ImportGarminActivity::class);

    // First import
    $result1 = $action->execute($user, $fitData);
    expect($result1->warnings)->toBeEmpty();

    // Second import — should warn
    $result2 = $action->execute($user, $fitData);
    expect($result2->warnings)->not->toBeEmpty()
        ->and($result2->warnings[0])->toContain('Possible duplicate');
});

// --- Superset Detection ---

it('detects A-B-A-B pattern as a superset block', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    $benchPress = Exercise::factory()
        ->withGarminMapping(GarminExerciseCategory::BenchPress, 0)
        ->create(['name' => 'Bench Press']);

    $row = Exercise::factory()
        ->withGarminMapping(GarminExerciseCategory::Row, 0)
        ->create(['name' => 'Row']);

    $fitData = (new FitActivityFixtureBuilder)
        ->withSession(sport: 10, subSport: 20, totalElapsedTime: 2400)
        // Round 1
        ->addSet(setType: 0, repetitions: 10, weight: 80.0, exerciseCategory: GarminExerciseCategory::BenchPress->value, exerciseName: 0)
        ->addSet(setType: 0, repetitions: 10, weight: 80.0, exerciseCategory: GarminExerciseCategory::BenchPress->value, exerciseName: 0)
        ->addSet(setType: 0, repetitions: 10, weight: 80.0, exerciseCategory: GarminExerciseCategory::BenchPress->value, exerciseName: 0)
        ->addSet(setType: 0, repetitions: 12, weight: 60.0, exerciseCategory: GarminExerciseCategory::Row->value, exerciseName: 0)
        ->addSet(setType: 0, repetitions: 12, weight: 60.0, exerciseCategory: GarminExerciseCategory::Row->value, exerciseName: 0)
        ->addSet(setType: 0, repetitions: 12, weight: 60.0, exerciseCategory: GarminExerciseCategory::Row->value, exerciseName: 0)
        // Round 2
        ->addSet(setType: 0, repetitions: 10, weight: 82.5, exerciseCategory: GarminExerciseCategory::BenchPress->value, exerciseName: 0)
        ->addSet(setType: 0, repetitions: 10, weight: 82.5, exerciseCategory: GarminExerciseCategory::BenchPress->value, exerciseName: 0)
        ->addSet(setType: 0, repetitions: 10, weight: 82.5, exerciseCategory: GarminExerciseCategory::BenchPress->value, exerciseName: 0)
        ->addSet(setType: 0, repetitions: 12, weight: 62.5, exerciseCategory: GarminExerciseCategory::Row->value, exerciseName: 0)
        ->addSet(setType: 0, repetitions: 12, weight: 62.5, exerciseCategory: GarminExerciseCategory::Row->value, exerciseName: 0)
        ->addSet(setType: 0, repetitions: 12, weight: 62.5, exerciseCategory: GarminExerciseCategory::Row->value, exerciseName: 0)
        ->addExerciseTitle(GarminExerciseCategory::BenchPress->value, 0, 'Bench Press')
        ->addExerciseTitle(GarminExerciseCategory::Row->value, 0, 'Row')
        ->build();

    $action = app(ImportGarminActivity::class);
    $result = $action->execute($user, $fitData);

    // Should create 1 superset block with 2 exercises
    $sections = $result->workout->sections;
    expect($sections)->toHaveCount(1);

    $blocks = $sections->first()->blocks;
    expect($blocks)->toHaveCount(1);
    expect($blocks->first()->block_type)->toBe(\App\Enums\Workout\BlockType::Superset);
    expect($blocks->first()->exercises)->toHaveCount(2);

    // Bench Press: 6 sets total (3+3)
    $benchExercise = $blocks->first()->exercises->first(fn ($e) => $e->exercise_id === $benchPress->id);
    $benchSets = ExerciseSet::where('block_exercise_id', $benchExercise->id)->get();
    expect($benchSets)->toHaveCount(6);

    // Row: 6 sets total (3+3)
    $rowExercise = $blocks->first()->exercises->first(fn ($e) => $e->exercise_id === $row->id);
    $rowSets = ExerciseSet::where('block_exercise_id', $rowExercise->id)->get();
    expect($rowSets)->toHaveCount(6);
});

it('keeps straight sets when exercises do not repeat', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    $fitData = (new FitActivityFixtureBuilder)
        ->withSession(sport: 10, subSport: 20, totalElapsedTime: 1800)
        ->addSet(setType: 0, repetitions: 10, weight: 80.0, exerciseCategory: GarminExerciseCategory::BenchPress->value, exerciseName: 0)
        ->addSet(setType: 0, repetitions: 10, weight: 80.0, exerciseCategory: GarminExerciseCategory::BenchPress->value, exerciseName: 0)
        ->addSet(setType: 0, repetitions: 12, weight: 60.0, exerciseCategory: GarminExerciseCategory::Squat->value, exerciseName: 0)
        ->addSet(setType: 0, repetitions: 12, weight: 60.0, exerciseCategory: GarminExerciseCategory::Squat->value, exerciseName: 0)
        ->addExerciseTitle(GarminExerciseCategory::BenchPress->value, 0, 'Bench Press')
        ->addExerciseTitle(GarminExerciseCategory::Squat->value, 0, 'Squat')
        ->build();

    $action = app(ImportGarminActivity::class);
    $result = $action->execute($user, $fitData);

    // 2 straight set blocks
    $blocks = $result->workout->sections->first()->blocks;
    expect($blocks)->toHaveCount(2);
    expect($blocks[0]->block_type)->toBe(\App\Enums\Workout\BlockType::StraightSets);
    expect($blocks[1]->block_type)->toBe(\App\Enums\Workout\BlockType::StraightSets);
});

// --- Mixed Workout ---

it('imports both strength sets and cardio laps from a mixed workout', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    $fitData = (new FitActivityFixtureBuilder)
        ->withSession(sport: 10, subSport: 26, totalElapsedTime: 3600, totalDistance: 3000, totalCalories: 500)
        // Strength sets
        ->addSet(setType: 0, repetitions: 10, weight: 80.0, exerciseCategory: GarminExerciseCategory::BenchPress->value, exerciseName: 0)
        ->addSet(setType: 0, repetitions: 10, weight: 80.0, exerciseCategory: GarminExerciseCategory::BenchPress->value, exerciseName: 0)
        ->addExerciseTitle(GarminExerciseCategory::BenchPress->value, 0, 'Bench Press')
        // Cardio laps with distance
        ->addLap(totalElapsedTime: 600, totalDistance: 1000, avgHeartRate: 160)
        ->addLap(totalElapsedTime: 600, totalDistance: 1000, avgHeartRate: 165)
        ->addLap(totalElapsedTime: 600, totalDistance: 1000, avgHeartRate: 170)
        ->build();

    $action = app(ImportGarminActivity::class);
    $result = $action->execute($user, $fitData);

    // Should have both strength and cardio blocks
    $blocks = $result->workout->sections->first()->blocks;

    $strengthBlocks = $blocks->filter(fn ($b) => $b->block_type !== \App\Enums\Workout\BlockType::DistanceDuration);
    $cardioBlocks = $blocks->filter(fn ($b) => $b->block_type === \App\Enums\Workout\BlockType::DistanceDuration);

    expect($strengthBlocks)->toHaveCount(1);
    expect($cardioBlocks)->toHaveCount(1);

    // 2 strength sets + 3 cardio laps = 5 exercise sets
    expect(ExerciseSet::count())->toBe(5);
});

it('ignores laps without distance in mixed workouts', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    $fitData = (new FitActivityFixtureBuilder)
        ->withSession(sport: 10, subSport: 20, totalElapsedTime: 1800)
        ->addSet(setType: 0, repetitions: 10, weight: 80.0, exerciseCategory: GarminExerciseCategory::BenchPress->value, exerciseName: 0)
        ->addExerciseTitle(GarminExerciseCategory::BenchPress->value, 0, 'Bench Press')
        // Lap with no distance (summary lap from strength session)
        ->addLap(totalElapsedTime: 1800)
        ->build();

    $action = app(ImportGarminActivity::class);
    $result = $action->execute($user, $fitData);

    // Only strength block, no cardio block
    $blocks = $result->workout->sections->first()->blocks;
    $cardioBlocks = $blocks->filter(fn ($b) => $b->block_type === \App\Enums\Workout\BlockType::DistanceDuration);

    expect($cardioBlocks)->toBeEmpty();
    expect(ExerciseSet::count())->toBe(1);
});

// --- Acceptance Tests with Real FIT Files ---

it('imports a real .fit activity file without errors', function (string $filename) {
    $user = User::factory()->withTimezone('Europe/Amsterdam')->create();

    $fitData = file_get_contents(base_path("tests/fixtures/fit/{$filename}"));

    $action = app(ImportGarminActivity::class);
    $result = $action->execute($user, $fitData);

    expect($result->workout)->not->toBeNull()
        ->and($result->workout->isCompleted())->toBeTrue()
        ->and($result->workout->source)->toBe('garmin_fit')
        ->and($result->workout->sections)->not->toBeEmpty();
})->with([
    '22245117138_ACTIVITY.fit',
    '22202494444_ACTIVITY.fit',
    '22304009849_ACTIVITY.fit',
]);
