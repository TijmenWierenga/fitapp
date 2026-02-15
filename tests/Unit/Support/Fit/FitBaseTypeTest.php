<?php

use App\Support\Fit\FitBaseType;

it('returns correct sizes for each type', function () {
    expect(FitBaseType::Enum->size())->toBe(1)
        ->and(FitBaseType::UInt8->size())->toBe(1)
        ->and(FitBaseType::UInt16->size())->toBe(2)
        ->and(FitBaseType::UInt32->size())->toBe(4)
        ->and(FitBaseType::SInt32->size())->toBe(4)
        ->and(FitBaseType::String->size())->toBe(1);
});

it('returns correct invalid values for each type', function () {
    expect(FitBaseType::Enum->invalidValue())->toBe(0xFF)
        ->and(FitBaseType::UInt8->invalidValue())->toBe(0xFF)
        ->and(FitBaseType::UInt16->invalidValue())->toBe(0xFFFF)
        ->and(FitBaseType::UInt32->invalidValue())->toBe(0xFFFFFFFF)
        ->and(FitBaseType::SInt32->invalidValue())->toBe(0x7FFFFFFF)
        ->and(FitBaseType::String->invalidValue())->toBe(0x00);
});
