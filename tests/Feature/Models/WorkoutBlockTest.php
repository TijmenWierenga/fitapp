<?php

use App\Enums\Workout\BlockType;
use App\Enums\Workout\ExerciseGroupType;
use App\Enums\Workout\IntervalIntensity;
use App\Models\ExerciseGroup;
use App\Models\IntervalBlock;
use App\Models\NoteBlock;
use App\Models\RestBlock;
use App\Models\Workout;
use App\Models\WorkoutBlock;

it('can create a root group block', function () {
    $workout = Workout::factory()->create();

    $block = WorkoutBlock::factory()->group('Warm-up')->create([
        'workout_id' => $workout->id,
        'position' => 0,
    ]);

    expect($block->type)->toBe(BlockType::Group)
        ->and($block->label)->toBe('Warm-up')
        ->and($block->workout->id)->toBe($workout->id)
        ->and($block->parent)->toBeNull()
        ->and($block->depth())->toBe(1);
});

it('can nest blocks up to max depth', function () {
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

    expect($level1->depth())->toBe(1)
        ->and($level2->depth())->toBe(2)
        ->and($level3->depth())->toBe(3);
});

it('loads children in position order', function () {
    $workout = Workout::factory()->create();

    $parent = WorkoutBlock::factory()->group()->create([
        'workout_id' => $workout->id,
        'position' => 0,
    ]);

    $child2 = WorkoutBlock::factory()->group()->create([
        'workout_id' => $workout->id,
        'parent_id' => $parent->id,
        'position' => 1,
    ]);

    $child1 = WorkoutBlock::factory()->group()->create([
        'workout_id' => $workout->id,
        'parent_id' => $parent->id,
        'position' => 0,
    ]);

    $children = $parent->children;

    expect($children)->toHaveCount(2)
        ->and($children->first()->id)->toBe($child1->id)
        ->and($children->last()->id)->toBe($child2->id);
});

it('can associate an interval block via polymorphic relation', function () {
    $workout = Workout::factory()->create();

    $interval = IntervalBlock::factory()->create([
        'duration_seconds' => 600,
        'intensity' => IntervalIntensity::Threshold,
    ]);

    $block = WorkoutBlock::factory()->interval()->create([
        'workout_id' => $workout->id,
        'position' => 0,
        'blockable_type' => 'interval_block',
        'blockable_id' => $interval->id,
    ]);

    expect($block->blockable)->toBeInstanceOf(IntervalBlock::class)
        ->and($block->blockable->duration_seconds)->toBe(600)
        ->and($block->blockable->intensity)->toBe(IntervalIntensity::Threshold);
});

it('can associate an exercise group via polymorphic relation', function () {
    $workout = Workout::factory()->create();

    $exerciseGroup = ExerciseGroup::factory()->superset()->create();

    $block = WorkoutBlock::factory()->exerciseGroup()->create([
        'workout_id' => $workout->id,
        'position' => 0,
        'blockable_type' => 'exercise_group',
        'blockable_id' => $exerciseGroup->id,
    ]);

    expect($block->blockable)->toBeInstanceOf(ExerciseGroup::class)
        ->and($block->blockable->group_type)->toBe(ExerciseGroupType::Superset);
});

it('can associate a rest block via polymorphic relation', function () {
    $workout = Workout::factory()->create();
    $rest = RestBlock::factory()->create(['duration_seconds' => 120]);

    $block = WorkoutBlock::factory()->rest()->create([
        'workout_id' => $workout->id,
        'position' => 0,
        'blockable_type' => 'rest_block',
        'blockable_id' => $rest->id,
    ]);

    expect($block->blockable)->toBeInstanceOf(RestBlock::class)
        ->and($block->blockable->duration_seconds)->toBe(120);
});

it('can associate a note block via polymorphic relation', function () {
    $workout = Workout::factory()->create();
    $note = NoteBlock::factory()->create(['content' => 'Focus on form']);

    $block = WorkoutBlock::factory()->note()->create([
        'workout_id' => $workout->id,
        'position' => 0,
        'blockable_type' => 'note_block',
        'blockable_id' => $note->id,
    ]);

    expect($block->blockable)->toBeInstanceOf(NoteBlock::class)
        ->and($block->blockable->content)->toBe('Focus on form');
});

it('loads the block tree from the workout', function () {
    $workout = Workout::factory()->create();

    $root = WorkoutBlock::factory()->group('Main Set')->create([
        'workout_id' => $workout->id,
        'position' => 0,
    ]);

    $interval = IntervalBlock::factory()->create();
    WorkoutBlock::factory()->interval()->create([
        'workout_id' => $workout->id,
        'parent_id' => $root->id,
        'position' => 0,
        'blockable_type' => 'interval_block',
        'blockable_id' => $interval->id,
    ]);

    $tree = $workout->blockTree;

    expect($tree)->toHaveCount(1)
        ->and($tree->first()->label)->toBe('Main Set')
        ->and($tree->first()->nestedChildren)->toHaveCount(1)
        ->and($tree->first()->nestedChildren->first()->blockable)->toBeInstanceOf(IntervalBlock::class);
});

it('supports repeat count on blocks', function () {
    $workout = Workout::factory()->create();

    $block = WorkoutBlock::factory()->group()->create([
        'workout_id' => $workout->id,
        'position' => 0,
        'repeat_count' => 4,
        'rest_between_repeats_seconds' => 90,
    ]);

    expect($block->repeat_count)->toBe(4)
        ->and($block->rest_between_repeats_seconds)->toBe(90);
});
