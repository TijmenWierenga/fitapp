<?php

use App\ValueObjects\TimeValue;

test('can create time value from minutes and seconds', function () {
    $time = new TimeValue(5, 30);
    
    expect($time->minutes)->toBe(5)
        ->and($time->seconds)->toBe(30);
});

test('can convert time value to total seconds', function () {
    $time = new TimeValue(5, 30);
    
    expect($time->toSeconds())->toBe(330);
});

test('can create time value from total seconds', function () {
    $time = TimeValue::fromSeconds(330);
    
    expect($time->minutes)->toBe(5)
        ->and($time->seconds)->toBe(30);
});

test('formats time value correctly', function () {
    $time = new TimeValue(5, 9);
    
    expect($time->format())->toBe('5:09');
});

test('validates time value correctly', function () {
    $valid = new TimeValue(5, 0);
    $tooShort = new TimeValue(0, 5);
    $tooLong = new TimeValue(361, 0);
    
    expect($valid->isValid())->toBeTrue()
        ->and($tooShort->isValid())->toBeFalse()
        ->and($tooLong->isValid())->toBeFalse();
});

test('throws exception for invalid seconds', function () {
    new TimeValue(5, 60);
})->throws(\InvalidArgumentException::class);

test('throws exception for negative minutes', function () {
    new TimeValue(-1, 30);
})->throws(\InvalidArgumentException::class);
