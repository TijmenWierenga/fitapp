<?php

use App\Enums\Workout\BlockType;

it('returns correct fields for each block type', function (BlockType $type, array $expectedFields) {
    expect($type->fields())->toBe($expectedFields);
})->with([
    'straight_sets' => [BlockType::StraightSets, []],
    'circuit' => [BlockType::Circuit, ['rounds', 'rest_between_exercises', 'rest_between_rounds']],
    'superset' => [BlockType::Superset, ['rounds', 'rest_between_rounds']],
    'interval' => [BlockType::Interval, ['rounds', 'work_interval', 'rest_interval']],
    'amrap' => [BlockType::Amrap, ['time_cap']],
    'for_time' => [BlockType::ForTime, ['rounds', 'time_cap']],
    'emom' => [BlockType::Emom, ['rounds', 'work_interval']],
    'distance_duration' => [BlockType::DistanceDuration, []],
    'rest' => [BlockType::Rest, []],
]);

it('checks hasField correctly', function () {
    expect(BlockType::Circuit->hasField('rounds'))->toBeTrue()
        ->and(BlockType::Circuit->hasField('rest_between_exercises'))->toBeTrue()
        ->and(BlockType::Circuit->hasField('work_interval'))->toBeFalse()
        ->and(BlockType::Interval->hasField('rest_interval'))->toBeTrue()
        ->and(BlockType::Emom->hasField('rest_interval'))->toBeFalse()
        ->and(BlockType::StraightSets->hasField('rounds'))->toBeFalse()
        ->and(BlockType::ForTime->hasField('rounds'))->toBeTrue()
        ->and(BlockType::ForTime->hasField('time_cap'))->toBeTrue();
});

it('generates a field guide covering all block types', function () {
    $guide = BlockType::fieldGuide();

    expect($guide)->toHaveCount(count(BlockType::cases()));

    foreach (BlockType::cases() as $case) {
        expect($guide)->toHaveKey($case->value)
            ->and($guide[$case->value])->toBe($case->fields());
    }
});
