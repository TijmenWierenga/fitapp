<?php

use App\DataTransferObjects\Workout\BlockData;
use App\DataTransferObjects\Workout\CardioExerciseData;
use App\DataTransferObjects\Workout\ExerciseData;
use App\DataTransferObjects\Workout\SectionData;
use App\DataTransferObjects\Workout\StrengthExerciseData;
use App\Enums\Workout\BlockType;
use App\Enums\Workout\ExerciseType;

it('creates SectionData from a fully nested array', function () {
    $data = [
        'name' => 'Warm-up',
        'order' => 0,
        'notes' => 'Light movement',
        'blocks' => [
            [
                'block_type' => 'circuit',
                'order' => 0,
                'rounds' => 3,
                'rest_between_exercises' => 30,
                'exercises' => [
                    [
                        'name' => 'Push-up',
                        'order' => 0,
                        'type' => 'strength',
                        'target_sets' => 3,
                        'target_reps_max' => 15,
                    ],
                    [
                        'name' => 'Light Jog',
                        'order' => 1,
                        'type' => 'cardio',
                        'target_duration' => 300,
                    ],
                ],
            ],
        ],
    ];

    $section = SectionData::fromArray($data);

    expect($section->name)->toBe('Warm-up')
        ->and($section->order)->toBe(0)
        ->and($section->notes)->toBe('Light movement')
        ->and($section->blocks)->toHaveCount(1);

    $block = $section->blocks->first();
    expect($block)->toBeInstanceOf(BlockData::class)
        ->and($block->blockType)->toBe(BlockType::Circuit)
        ->and($block->rounds)->toBe(3)
        ->and($block->restBetweenExercises)->toBe(30)
        ->and($block->exercises)->toHaveCount(2);

    $pushUp = $block->exercises->first();
    expect($pushUp)->toBeInstanceOf(ExerciseData::class)
        ->and($pushUp->name)->toBe('Push-up')
        ->and($pushUp->type)->toBe(ExerciseType::Strength)
        ->and($pushUp->exerciseable)->toBeInstanceOf(StrengthExerciseData::class)
        ->and($pushUp->exerciseable->targetSets)->toBe(3)
        ->and($pushUp->exerciseable->targetRepsMax)->toBe(15);

    $jog = $block->exercises->last();
    expect($jog->type)->toBe(ExerciseType::Cardio)
        ->and($jog->exerciseable)->toBeInstanceOf(CardioExerciseData::class)
        ->and($jog->exerciseable->targetDuration)->toBe(300);
});

it('creates SectionData with empty blocks', function () {
    $section = SectionData::fromArray([
        'name' => 'Rest',
        'order' => 2,
    ]);

    expect($section->name)->toBe('Rest')
        ->and($section->order)->toBe(2)
        ->and($section->notes)->toBeNull()
        ->and($section->blocks)->toHaveCount(0);
});
