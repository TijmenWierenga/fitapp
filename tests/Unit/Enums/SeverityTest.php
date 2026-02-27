<?php

use App\Enums\Severity;

test('severity enum has correct cases', function () {
    $cases = Severity::cases();

    expect($cases)->toHaveCount(3)
        ->and(Severity::Mild->value)->toBe('mild')
        ->and(Severity::Moderate->value)->toBe('moderate')
        ->and(Severity::Severe->value)->toBe('severe');
});

test('severity label returns correct values', function (Severity $severity, string $expected) {
    expect($severity->label())->toBe($expected);
})->with([
    [Severity::Mild, 'Mild'],
    [Severity::Moderate, 'Moderate'],
    [Severity::Severe, 'Severe'],
]);

test('severity color returns correct values', function (Severity $severity, string $expected) {
    expect($severity->color())->toBe($expected);
})->with([
    [Severity::Mild, 'lime'],
    [Severity::Moderate, 'amber'],
    [Severity::Severe, 'red'],
]);
