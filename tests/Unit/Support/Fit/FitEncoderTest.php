<?php

use App\Support\Fit\FitBaseType;
use App\Support\Fit\FitEncoder;
use App\Support\Fit\FitField;
use App\Support\Fit\FitMessage;

it('produces a valid FIT file with correct header magic', function () {
    $encoder = new FitEncoder;

    $messages = [
        new FitMessage(0, 0, [
            new FitField(0, FitBaseType::Enum, 5),
        ]),
    ];

    $output = $encoder->encode($messages);

    // Header size = 14
    expect(ord($output[0]))->toBe(14)
        // Protocol version
        ->and(ord($output[1]))->toBe(0x20)
        // ".FIT" magic bytes at offset 8
        ->and(substr($output, 8, 4))->toBe('.FIT');
});

it('sets correct data size in header', function () {
    $encoder = new FitEncoder;

    $message = new FitMessage(0, 0, [
        new FitField(0, FitBaseType::UInt8, 42),
    ]);

    $output = $encoder->encode([$message]);

    $dataSize = unpack('V', substr($output, 4, 4))[1];

    // Data = definition + data record
    // Definition: 1 (header) + 1 (reserved) + 1 (arch) + 2 (global msg) + 1 (num fields) + 3 (field def) = 9
    // Data: 1 (header) + 1 (value) = 2
    // Total: 11
    expect($dataSize)->toBe(11);
});

it('appends valid CRC at end of file', function () {
    $encoder = new FitEncoder;

    $messages = [
        new FitMessage(0, 0, [
            new FitField(0, FitBaseType::Enum, 5),
        ]),
    ];

    $output = $encoder->encode($messages);

    // File CRC is last 2 bytes
    $fileCrc = unpack('v', substr($output, -2))[1];
    $fileContent = substr($output, 0, -2);
    $calculatedCrc = FitEncoder::crc16($fileContent);

    expect($fileCrc)->toBe($calculatedCrc);
});

it('includes header CRC at bytes 12-13', function () {
    $encoder = new FitEncoder;

    $messages = [
        new FitMessage(0, 0, [
            new FitField(0, FitBaseType::Enum, 5),
        ]),
    ];

    $output = $encoder->encode($messages);

    $headerCrc = unpack('v', substr($output, 12, 2))[1];
    $calculatedCrc = FitEncoder::crc16(substr($output, 0, 12));

    expect($headerCrc)->toBe($calculatedCrc);
});

it('reuses definitions for identical message types', function () {
    $encoder = new FitEncoder;

    $msg1 = new FitMessage(0, 0, [new FitField(0, FitBaseType::UInt8, 1)]);
    $msg2 = new FitMessage(0, 0, [new FitField(0, FitBaseType::UInt8, 2)]);

    $output = $encoder->encode([$msg1, $msg2]);

    // Should have: 14 (header) + 9 (definition) + 2 (data1) + 2 (data2) + 2 (CRC) = 29
    // Only ONE definition, not two
    expect(strlen($output))->toBe(29);
});

it('re-emits definition when field sizes change', function () {
    $encoder = new FitEncoder;

    $msg1 = new FitMessage(0, 27, [new FitField(0, FitBaseType::String, 'AB')]);
    $msg2 = new FitMessage(0, 27, [new FitField(0, FitBaseType::String, 'ABCD')]);

    $output = $encoder->encode([$msg1, $msg2]);

    // Different string lengths require different definitions
    // msg1: def(9 bytes, field size 3) + data(1+3=4)
    // msg2: def(9 bytes, field size 5) + data(1+5=6)
    // Total: 14 + 9 + 4 + 9 + 6 + 2 = 44
    expect(strlen($output))->toBe(44);
});

it('produces correct CRC for known input', function () {
    // CRC-16/ARC for empty string should be 0
    expect(FitEncoder::crc16(''))->toBe(0);

    // CRC-16/ARC for "123456789" should be 0xBB3D
    expect(FitEncoder::crc16('123456789'))->toBe(0xBB3D);
});
