<?php

use App\Models\Block;
use App\Models\Section;
use App\Models\Workout;

it('belongs to a workout', function () {
    $section = Section::factory()->create();

    expect($section->workout)->toBeInstanceOf(Workout::class);
});

it('has many blocks ordered by order', function () {
    $section = Section::factory()->create();
    $block2 = Block::factory()->for($section)->create(['order' => 2]);
    $block1 = Block::factory()->for($section)->create(['order' => 1]);
    $block3 = Block::factory()->for($section)->create(['order' => 3]);

    $blocks = $section->blocks;

    expect($blocks)->toHaveCount(3)
        ->and($blocks[0]->id)->toBe($block1->id)
        ->and($blocks[1]->id)->toBe($block2->id)
        ->and($blocks[2]->id)->toBe($block3->id);
});

it('casts order to integer', function () {
    $section = Section::factory()->create(['order' => 2]);

    expect($section->order)->toBeInt()->toBe(2);
});
