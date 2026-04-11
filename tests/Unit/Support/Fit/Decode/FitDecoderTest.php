<?php

declare(strict_types=1);

use App\Exceptions\FitParseException;
use App\Support\Fit\Decode\FitDecoder;
use Tests\Support\FitActivityFixtureBuilder;

it('decodes a synthetic FIT activity file', function () {
    $builder = new FitActivityFixtureBuilder;
    $data = $builder
        ->withSession(sport: 1, subSport: 0, totalElapsedTime: 1800, totalDistance: 5000)
        ->addLap(totalElapsedTime: 600, totalDistance: 1000)
        ->addLap(totalElapsedTime: 600, totalDistance: 1000)
        ->build();

    $decoder = new FitDecoder;
    $messages = $decoder->decode($data);

    expect($messages)->toBeArray();

    $globalMessageNumbers = collect($messages)->pluck('globalMessageNumber')->unique()->sort()->values()->all();
    expect($globalMessageNumbers)->toContain(0)  // file_id
        ->toContain(18)  // session
        ->toContain(19); // lap
});

it('decodes the SDK sample activity fixture', function () {
    $data = file_get_contents(dirname(__DIR__, 4).'/fixtures/fit/Activity.fit');

    $decoder = new FitDecoder;
    $messages = $decoder->decode($data);

    expect($messages)->toBeArray()
        ->and(count($messages))->toBeGreaterThan(0);

    $globalMessageNumbers = collect($messages)->pluck('globalMessageNumber')->unique()->values()->all();
    expect($globalMessageNumbers)->toContain(0)  // file_id
        ->toContain(18)  // session
        ->toContain(19); // lap
});

it('decodes field values correctly from a synthetic fixture', function () {
    $builder = new FitActivityFixtureBuilder;
    $data = $builder
        ->withSession(sport: 2, subSport: 6, totalElapsedTime: 3600, totalDistance: 25000, totalCalories: 500, avgHeartRate: 145, maxHeartRate: 185)
        ->build();

    $decoder = new FitDecoder;
    $messages = $decoder->decode($data);

    $sessionMessages = collect($messages)->filter(fn ($m) => $m->globalMessageNumber === 18);
    expect($sessionMessages)->toHaveCount(1);

    $session = $sessionMessages->first();
    $fields = collect($session->fields);

    expect($fields->firstWhere('fieldNumber', 5)->value)->toBe(2)   // sport
        ->and($fields->firstWhere('fieldNumber', 6)->value)->toBe(6) // subSport
        ->and($fields->firstWhere('fieldNumber', 16)->value)->toBe(145) // avgHeartRate
        ->and($fields->firstWhere('fieldNumber', 17)->value)->toBe(185); // maxHeartRate
});

it('throws on invalid header', function () {
    $decoder = new FitDecoder;
    $decoder->decode('short');
})->throws(FitParseException::class, 'Invalid FIT file header.');

it('throws on invalid signature', function () {
    $decoder = new FitDecoder;
    $binary = pack('C', 14) // header size
        .pack('C', 0x20) // protocol
        .pack('v', 2100) // profile
        .pack('V', 0) // data size
        .'XFIT' // wrong signature
        .pack('v', 0); // header CRC

    // add file CRC
    $binary .= pack('v', 0);

    $decoder->decode($binary);
})->throws(FitParseException::class, 'Invalid FIT file signature');

it('throws on truncated file', function () {
    $builder = new FitActivityFixtureBuilder;
    $data = $builder->withSession(sport: 1, subSport: 0)->build();

    // Truncate the data
    $truncated = substr($data, 0, 20);

    $decoder = new FitDecoder;
    $decoder->decode($truncated);
})->throws(FitParseException::class);

it('throws on bad CRC', function () {
    $builder = new FitActivityFixtureBuilder;
    $data = $builder->withSession(sport: 1, subSport: 0)->build();

    // Corrupt a byte in the data section
    $data[15] = chr(ord($data[15]) ^ 0xFF);

    $decoder = new FitDecoder;
    $decoder->decode($data);
})->throws(FitParseException::class, 'CRC check failed');
