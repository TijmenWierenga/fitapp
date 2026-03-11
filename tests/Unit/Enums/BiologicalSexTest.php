<?php

use App\Enums\BiologicalSex;

it('has correct values', function () {
    expect(BiologicalSex::cases())->toHaveCount(2);
    expect(BiologicalSex::Male->value)->toBe('male');
    expect(BiologicalSex::Female->value)->toBe('female');
});

it('has labels', function () {
    expect(BiologicalSex::Male->label())->toBe('Male');
    expect(BiologicalSex::Female->label())->toBe('Female');
});
