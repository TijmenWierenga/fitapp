<?php

use App\Enums\Side;

test('side enum has correct cases', function () {
    $cases = Side::cases();

    expect($cases)->toHaveCount(4)
        ->and(Side::Left->value)->toBe('left')
        ->and(Side::Right->value)->toBe('right')
        ->and(Side::Both->value)->toBe('both')
        ->and(Side::NotApplicable->value)->toBe('not_applicable');
});

test('side label returns correct values', function (Side $side, string $expected) {
    expect($side->label())->toBe($expected);
})->with([
    [Side::Left, 'Left'],
    [Side::Right, 'Right'],
    [Side::Both, 'Both'],
    [Side::NotApplicable, 'N/A'],
]);
