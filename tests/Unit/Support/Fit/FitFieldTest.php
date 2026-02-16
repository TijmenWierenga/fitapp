<?php

use App\Support\Fit\FitBaseType;
use App\Support\Fit\FitField;

it('encodes uint8 value', function () {
    $field = new FitField(0, FitBaseType::UInt8, 42);

    expect($field->encode())->toBe(pack('C', 42))
        ->and($field->fieldSize())->toBe(1);
});

it('encodes enum value', function () {
    $field = new FitField(0, FitBaseType::Enum, 5);

    expect($field->encode())->toBe(pack('C', 5))
        ->and($field->fieldSize())->toBe(1);
});

it('encodes uint16 value in little-endian', function () {
    $field = new FitField(0, FitBaseType::UInt16, 0x1234);

    expect($field->encode())->toBe(pack('v', 0x1234))
        ->and($field->fieldSize())->toBe(2);
});

it('encodes uint32 value in little-endian', function () {
    $field = new FitField(0, FitBaseType::UInt32, 0x12345678);

    expect($field->encode())->toBe(pack('V', 0x12345678))
        ->and($field->fieldSize())->toBe(4);
});

it('encodes sint32 value', function () {
    $field = new FitField(0, FitBaseType::SInt32, -1);

    // -1 as unsigned 32-bit = 0xFFFFFFFF
    expect($field->encode())->toBe(pack('V', 0xFFFFFFFF))
        ->and($field->fieldSize())->toBe(4);
});

it('encodes null uint8 as invalid value', function () {
    $field = new FitField(0, FitBaseType::UInt8, null);

    expect($field->encode())->toBe(pack('C', 0xFF));
});

it('encodes null uint16 as invalid value', function () {
    $field = new FitField(0, FitBaseType::UInt16, null);

    expect($field->encode())->toBe(pack('v', 0xFFFF));
});

it('encodes null uint32 as invalid value', function () {
    $field = new FitField(0, FitBaseType::UInt32, null);

    expect($field->encode())->toBe(pack('V', 0xFFFFFFFF));
});

it('encodes null sint32 as invalid value', function () {
    $field = new FitField(0, FitBaseType::SInt32, null);

    expect($field->encode())->toBe(pack('V', 0x7FFFFFFF));
});

it('encodes string value with null terminator', function () {
    $field = new FitField(0, FitBaseType::String, 'Run');

    $encoded = $field->encode();

    expect($encoded)->toBe("Run\x00")
        ->and($field->fieldSize())->toBe(4); // 3 chars + 1 null
});

it('encodes null string as null bytes', function () {
    $field = new FitField(0, FitBaseType::String, null);

    expect($field->encode())->toBe("\x00")
        ->and($field->fieldSize())->toBe(1);
});

it('uses explicit size for string fields', function () {
    $field = new FitField(0, FitBaseType::String, 'Hi', size: 10);

    $encoded = $field->encode();

    expect(strlen($encoded))->toBe(10)
        ->and($field->fieldSize())->toBe(10)
        ->and(substr($encoded, 0, 2))->toBe('Hi')
        ->and($encoded[2])->toBe("\x00");
});
