<?php

use App\Actions\ExportWorkoutFit;
use App\Models\Block;
use App\Models\BlockExercise;
use App\Models\Section;
use App\Models\StrengthExercise;
use App\Models\User;
use App\Models\Workout;
use App\Support\Fit\FitEncoder;

it('produces valid FIT output with correct header magic', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    $workout = Workout::factory()->for($user)->create(['name' => 'Test Export']);

    $section = Section::factory()->for($workout)->create(['name' => 'Main', 'order' => 0]);
    $block = Block::factory()->for($section)->create(['block_type' => 'straight_sets', 'order' => 0]);
    $strength = StrengthExercise::factory()->create(['target_sets' => 3]);
    BlockExercise::factory()->create([
        'block_id' => $block->id,
        'name' => 'Squat',
        'order' => 0,
        'exerciseable_type' => 'strength_exercise',
        'exerciseable_id' => $strength->id,
    ]);

    $export = app(ExportWorkoutFit::class);
    $data = $export->execute($workout);

    // Header size = 14
    expect(ord($data[0]))->toBe(14)
        // ".FIT" magic at offset 8
        ->and(substr($data, 8, 4))->toBe('.FIT');
});

it('produces valid CRC', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    $workout = Workout::factory()->for($user)->create();

    $export = app(ExportWorkoutFit::class);
    $data = $export->execute($workout);

    $fileCrc = unpack('v', substr($data, -2))[1];
    $fileContent = substr($data, 0, -2);

    expect($fileCrc)->toBe(FitEncoder::crc16($fileContent));
});

it('eager loads relationships when not already loaded', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    $workout = Workout::factory()->for($user)->create();

    $section = Section::factory()->for($workout)->create(['name' => 'Main', 'order' => 0]);
    $block = Block::factory()->for($section)->create(['block_type' => 'straight_sets', 'order' => 0]);
    $strength = StrengthExercise::factory()->create();
    BlockExercise::factory()->create([
        'block_id' => $block->id,
        'name' => 'Deadlift',
        'order' => 0,
        'exerciseable_type' => 'strength_exercise',
        'exerciseable_id' => $strength->id,
    ]);

    // Fresh workout without relationships loaded
    $freshWorkout = Workout::find($workout->id);

    $export = app(ExportWorkoutFit::class);
    $data = $export->execute($freshWorkout);

    // Should not throw, and should produce valid FIT
    expect(substr($data, 8, 4))->toBe('.FIT');
});
