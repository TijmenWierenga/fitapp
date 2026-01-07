<?php

use App\ValueObjects\PaceValue;

test('can create pace value from minutes and seconds', function () {
    $pace = new PaceValue(4, 30);
    
    expect($pace->minutes)->toBe(4)
        ->and($pace->seconds)->toBe(30);
});

test('can convert pace value to seconds per km', function () {
    $pace = new PaceValue(4, 30);
    
    expect($pace->toSecondsPerKm())->toBe(270);
});

test('can create pace value from seconds per km', function () {
    $pace = PaceValue::fromSecondsPerKm(270);
    
    expect($pace->minutes)->toBe(4)
        ->and($pace->seconds)->toBe(30);
});

test('formats pace value correctly', function () {
    $pace = new PaceValue(4, 30);
    
    expect($pace->format())->toBe('4:30 /km');
});

test('validates pace value correctly', function () {
    $valid = new PaceValue(4, 30);
    $tooFast = new PaceValue(1, 0);
    $tooSlow = new PaceValue(16, 0);
    
    expect($valid->isValid())->toBeTrue()
        ->and($tooFast->isValid())->toBeFalse()
        ->and($tooSlow->isValid())->toBeFalse();
});

test('throws exception for invalid seconds', function () {
    new PaceValue(4, 60);
})->throws(\InvalidArgumentException::class);

test('throws exception for negative minutes', function () {
    new PaceValue(-1, 30);
})->throws(\InvalidArgumentException::class);
