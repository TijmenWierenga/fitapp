<?php

use App\ValueObjects\DistanceValue;

test('can create distance value from kilometers and tens of meters', function () {
    $distance = new DistanceValue(4, 55);
    
    expect($distance->kilometers)->toBe(4)
        ->and($distance->tensOfMeters)->toBe(55);
});

test('can convert distance value to meters', function () {
    $distance = new DistanceValue(4, 55);
    
    expect($distance->toMeters())->toBe(4550);
});

test('can create distance value from meters', function () {
    $distance = DistanceValue::fromMeters(4550);
    
    expect($distance->kilometers)->toBe(4)
        ->and($distance->tensOfMeters)->toBe(55);
});

test('formats distance value correctly', function () {
    $distance = new DistanceValue(4, 55);
    
    expect($distance->format())->toBe('4.550 km');
});

test('validates distance value correctly', function () {
    $valid = new DistanceValue(5, 0);
    $tooShort = new DistanceValue(0, 0);
    $tooLong = new DistanceValue(101, 0);
    
    expect($valid->isValid())->toBeTrue()
        ->and($tooShort->isValid())->toBeFalse()
        ->and($tooLong->isValid())->toBeFalse();
});

test('throws exception for invalid tens of meters', function () {
    new DistanceValue(4, 100);
})->throws(\InvalidArgumentException::class);

test('throws exception for negative kilometers', function () {
    new DistanceValue(-1, 30);
})->throws(\InvalidArgumentException::class);

test('throws exception when creating from meters not divisible by 10', function () {
    DistanceValue::fromMeters(4555);
})->throws(\InvalidArgumentException::class);

test('handles edge cases correctly', function () {
    $min = DistanceValue::fromMeters(10);
    $max = DistanceValue::fromMeters(100000);
    
    expect($min->kilometers)->toBe(0)
        ->and($min->tensOfMeters)->toBe(1)
        ->and($max->kilometers)->toBe(100)
        ->and($max->tensOfMeters)->toBe(0);
});
