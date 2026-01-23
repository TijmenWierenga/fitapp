<?php

use App\Rules\Timezone;
use Illuminate\Support\Facades\Validator;

it('passes for valid timezone identifiers', function (string $timezone) {
    $validator = Validator::make(
        ['timezone' => $timezone],
        ['timezone' => new Timezone]
    );

    expect($validator->passes())->toBeTrue();
})->with([
    'UTC',
    'America/New_York',
    'Europe/Amsterdam',
    'Europe/London',
    'Asia/Tokyo',
    'Australia/Sydney',
    'Pacific/Auckland',
]);

it('fails for invalid timezone identifiers', function (string $timezone) {
    $validator = Validator::make(
        ['timezone' => $timezone],
        ['timezone' => new Timezone]
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('timezone'))->toBeTrue();
})->with([
    'Invalid/Timezone',
    'Foo/Bar',
    'NotATimezone',
    'US/East',
]);

it('passes for empty string when not required', function () {
    $validator = Validator::make(
        ['timezone' => ''],
        ['timezone' => new Timezone]
    );

    expect($validator->passes())->toBeTrue();
});

it('fails for empty string when required', function () {
    $validator = Validator::make(
        ['timezone' => ''],
        ['timezone' => ['required', new Timezone]]
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('timezone'))->toBeTrue();
});

it('fails for non-string values', function (mixed $value) {
    $validator = Validator::make(
        ['timezone' => $value],
        ['timezone' => new Timezone]
    );

    expect($validator->fails())->toBeTrue();
})->with([
    'integer' => 123,
    'float' => 12.5,
    'boolean true' => true,
    'boolean false' => false,
    'array' => [['America/New_York']],
]);
