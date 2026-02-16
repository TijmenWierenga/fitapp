<?php

use App\Support\Fit\FitBaseType;
use App\Support\Fit\FitField;
use App\Support\Fit\FitMessage;

it('encodes a definition message', function () {
    $message = new FitMessage(
        localMessageType: 0,
        globalMessageNumber: 0,
        fields: [
            new FitField(0, FitBaseType::Enum, 5),
            new FitField(1, FitBaseType::UInt16, 1),
        ],
    );

    $definition = $message->encodeDefinition();

    // Header: 0x40 | 0 = 0x40
    expect(ord($definition[0]))->toBe(0x40)
        // Reserved
        ->and(ord($definition[1]))->toBe(0)
        // Architecture: little-endian
        ->and(ord($definition[2]))->toBe(0)
        // Global message number: 0 (LE)
        ->and(unpack('v', substr($definition, 3, 2))[1])->toBe(0)
        // Number of fields: 2
        ->and(ord($definition[5]))->toBe(2)
        // Field 0: number=0, size=1, type=Enum(0x00)
        ->and(ord($definition[6]))->toBe(0)
        ->and(ord($definition[7]))->toBe(1)
        ->and(ord($definition[8]))->toBe(0x00)
        // Field 1: number=1, size=2, type=UInt16(0x84)
        ->and(ord($definition[9]))->toBe(1)
        ->and(ord($definition[10]))->toBe(2)
        ->and(ord($definition[11]))->toBe(0x84);
});

it('encodes a data message', function () {
    $message = new FitMessage(
        localMessageType: 0,
        globalMessageNumber: 0,
        fields: [
            new FitField(0, FitBaseType::Enum, 5),
            new FitField(1, FitBaseType::UInt16, 256),
        ],
    );

    $data = $message->encodeData();

    // Header: localMessageType = 0
    expect(ord($data[0]))->toBe(0)
        // Enum field: value 5
        ->and(ord($data[1]))->toBe(5)
        // UInt16 field: value 256 in LE = 0x00, 0x01
        ->and(ord($data[2]))->toBe(0x00)
        ->and(ord($data[3]))->toBe(0x01);
});

it('uses local message type in headers', function () {
    $message = new FitMessage(
        localMessageType: 2,
        globalMessageNumber: 27,
        fields: [
            new FitField(0, FitBaseType::UInt8, 1),
        ],
    );

    $definition = $message->encodeDefinition();
    $data = $message->encodeData();

    // Definition header: 0x40 | 2 = 0x42
    expect(ord($definition[0]))->toBe(0x42)
        // Data header: 2
        ->and(ord($data[0]))->toBe(2);
});
