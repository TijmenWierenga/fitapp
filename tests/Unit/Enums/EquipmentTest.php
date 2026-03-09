<?php

use App\Enums\Equipment;

it('has 12 equipment types', function () {
    expect(Equipment::cases())->toHaveCount(12);
});

it('has correct values matching exercise database', function () {
    $values = array_map(fn (Equipment $e): string => $e->value, Equipment::cases());

    expect($values)->toContain('bands')
        ->toContain('barbell')
        ->toContain('body only')
        ->toContain('cable')
        ->toContain('dumbbell')
        ->toContain('e-z curl bar')
        ->toContain('exercise ball')
        ->toContain('foam roll')
        ->toContain('kettlebells')
        ->toContain('machine')
        ->toContain('medicine ball')
        ->toContain('other');
});

it('returns home equipment options excluding BodyOnly and Other', function () {
    $options = Equipment::homeEquipmentOptions();

    expect($options)->toHaveCount(10)
        ->not->toContain(Equipment::BodyOnly)
        ->not->toContain(Equipment::Other);
});

it('has labels', function () {
    expect(Equipment::Bands->label())->toBe('Resistance Bands');
    expect(Equipment::Barbell->label())->toBe('Barbell');
    expect(Equipment::BodyOnly->label())->toBe('Body Only');
    expect(Equipment::EZCurlBar->label())->toBe('EZ Curl Bar');
});
