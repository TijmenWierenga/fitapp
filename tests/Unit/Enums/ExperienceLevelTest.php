<?php

use App\Enums\ExperienceLevel;

it('has correct values', function () {
    expect(ExperienceLevel::cases())->toHaveCount(3);
    expect(ExperienceLevel::Beginner->value)->toBe('beginner');
    expect(ExperienceLevel::Intermediate->value)->toBe('intermediate');
    expect(ExperienceLevel::Advanced->value)->toBe('advanced');
});

it('has labels', function () {
    expect(ExperienceLevel::Beginner->label())->toBe('Beginner');
    expect(ExperienceLevel::Intermediate->label())->toBe('Intermediate');
    expect(ExperienceLevel::Advanced->label())->toBe('Advanced');
});

it('has descriptions', function () {
    expect(ExperienceLevel::Beginner->description())->toContain('Less than 1 year');
    expect(ExperienceLevel::Intermediate->description())->toContain('1-3 years');
    expect(ExperienceLevel::Advanced->description())->toContain('3+ years');
});
