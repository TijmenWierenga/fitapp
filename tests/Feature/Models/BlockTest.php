<?php

use App\Enums\Workout\BlockType;
use App\Models\Block;
use App\Models\BlockExercise;
use App\Models\Section;
use App\Models\StrengthExercise;

it('belongs to a section', function () {
    $block = Block::factory()->create();

    expect($block->section)->toBeInstanceOf(Section::class);
});

it('has many exercises ordered by order', function () {
    $block = Block::factory()->create();

    $strength1 = StrengthExercise::factory()->create();
    $strength2 = StrengthExercise::factory()->create();

    $ex2 = BlockExercise::factory()->create([
        'block_id' => $block->id,
        'order' => 2,
        'exerciseable_type' => 'strength_exercise',
        'exerciseable_id' => $strength1->id,
    ]);
    $ex1 = BlockExercise::factory()->create([
        'block_id' => $block->id,
        'order' => 1,
        'exerciseable_type' => 'strength_exercise',
        'exerciseable_id' => $strength2->id,
    ]);

    $exercises = $block->exercises;

    expect($exercises)->toHaveCount(2)
        ->and($exercises[0]->id)->toBe($ex1->id)
        ->and($exercises[1]->id)->toBe($ex2->id);
});

it('casts block_type to BlockType enum', function () {
    $block = Block::factory()->create(['block_type' => BlockType::Circuit]);

    expect($block->block_type)->toBe(BlockType::Circuit);
});

it('casts timing fields to integers', function () {
    $block = Block::factory()->create([
        'rounds' => 3,
        'rest_between_exercises' => 30,
        'rest_between_rounds' => 60,
        'time_cap' => 900,
        'work_interval' => 45,
        'rest_interval' => 15,
    ]);

    expect($block->rounds)->toBeInt()->toBe(3)
        ->and($block->rest_between_exercises)->toBeInt()->toBe(30)
        ->and($block->rest_between_rounds)->toBeInt()->toBe(60)
        ->and($block->time_cap)->toBeInt()->toBe(900)
        ->and($block->work_interval)->toBeInt()->toBe(45)
        ->and($block->rest_interval)->toBeInt()->toBe(15);
});

it('has factory states for different block types', function () {
    expect(Block::factory()->circuit()->create()->block_type)->toBe(BlockType::Circuit)
        ->and(Block::factory()->superset()->create()->block_type)->toBe(BlockType::Superset)
        ->and(Block::factory()->interval()->create()->block_type)->toBe(BlockType::Interval)
        ->and(Block::factory()->amrap()->create()->block_type)->toBe(BlockType::Amrap)
        ->and(Block::factory()->forTime()->create()->block_type)->toBe(BlockType::ForTime)
        ->and(Block::factory()->emom()->create()->block_type)->toBe(BlockType::Emom)
        ->and(Block::factory()->distanceDuration()->create()->block_type)->toBe(BlockType::DistanceDuration)
        ->and(Block::factory()->rest()->create()->block_type)->toBe(BlockType::Rest);
});
