<?php

use App\Models\Workout;
use App\Models\WorkoutBlock;
use App\Rules\MaxBlockDepth;

it('passes when parent is at depth 1', function () {
    $workout = Workout::factory()->create();

    $parent = WorkoutBlock::factory()->group()->create([
        'workout_id' => $workout->id,
        'position' => 0,
    ]);

    $validator = validator(
        ['parent_id' => $parent->id],
        ['parent_id' => [new MaxBlockDepth]],
    );

    expect($validator->passes())->toBeTrue();
});

it('passes when parent is at depth 2', function () {
    $workout = Workout::factory()->create();

    $level1 = WorkoutBlock::factory()->group()->create([
        'workout_id' => $workout->id,
        'position' => 0,
    ]);

    $level2 = WorkoutBlock::factory()->group()->create([
        'workout_id' => $workout->id,
        'parent_id' => $level1->id,
        'position' => 0,
    ]);

    $validator = validator(
        ['parent_id' => $level2->id],
        ['parent_id' => [new MaxBlockDepth]],
    );

    expect($validator->passes())->toBeTrue();
});

it('fails when parent is at max depth', function () {
    $workout = Workout::factory()->create();

    $level1 = WorkoutBlock::factory()->group()->create([
        'workout_id' => $workout->id,
        'position' => 0,
    ]);

    $level2 = WorkoutBlock::factory()->group()->create([
        'workout_id' => $workout->id,
        'parent_id' => $level1->id,
        'position' => 0,
    ]);

    $level3 = WorkoutBlock::factory()->group()->create([
        'workout_id' => $workout->id,
        'parent_id' => $level2->id,
        'position' => 0,
    ]);

    $validator = validator(
        ['parent_id' => $level3->id],
        ['parent_id' => [new MaxBlockDepth]],
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('parent_id'))->toContain('Maximum nesting depth');
});

it('passes when parent_id is null', function () {
    $validator = validator(
        ['parent_id' => null],
        ['parent_id' => ['nullable', new MaxBlockDepth]],
    );

    expect($validator->passes())->toBeTrue();
});
