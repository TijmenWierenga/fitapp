<?php

use App\Enums\Equipment;

it('has 19 equipment types', function () {
    expect(Equipment::cases())->toHaveCount(19);
});

it('has correct values matching exercise database', function () {
    $values = array_map(fn (Equipment $e): string => $e->value, Equipment::cases());

    expect($values)->toContain('bands')
        ->toContain('barbell')
        ->toContain('box')
        ->toContain('cable')
        ->toContain('dip station')
        ->toContain('dumbbell')
        ->toContain('e-z curl bar')
        ->toContain('exercise ball')
        ->toContain('foam roll')
        ->toContain('kettlebells')
        ->toContain('machine')
        ->toContain('medicine ball')
        ->toContain('other')
        ->toContain('pull-up bar')
        ->toContain('rings')
        ->toContain('sled')
        ->toContain('suspension trainer')
        ->toContain('trap bar')
        ->toContain('weight plate');
});

it('does not contain removed body only value', function () {
    $values = array_map(fn (Equipment $e): string => $e->value, Equipment::cases());

    expect($values)->not->toContain('body only');
});

it('returns home equipment options excluding gym-only and generic equipment', function () {
    $options = Equipment::homeEquipmentOptions();

    expect($options)->toHaveCount(15)
        ->not->toContain(Equipment::Other)
        ->not->toContain(Equipment::Sled)
        ->not->toContain(Equipment::Machine)
        ->not->toContain(Equipment::Cable);
});

it('has labels for all cases', function () {
    foreach (Equipment::cases() as $case) {
        expect($case->label())->toBeString()->not->toBeEmpty();
    }
});

it('has correct labels for new equipment types', function () {
    expect(Equipment::PullUpBar->label())->toBe('Pull-Up Bar');
    expect(Equipment::SuspensionTrainer->label())->toBe('Suspension Trainer');
    expect(Equipment::Rings->label())->toBe('Gymnastic Rings');
    expect(Equipment::WeightPlate->label())->toBe('Weight Plate');
    expect(Equipment::Box->label())->toBe('Plyo Box');
    expect(Equipment::DipStation->label())->toBe('Dip/Parallel Bars');
    expect(Equipment::Sled->label())->toBe('Sled');
    expect(Equipment::TrapBar->label())->toBe('Trap Bar');
});

it('has correct labels for existing equipment types', function () {
    expect(Equipment::Bands->label())->toBe('Resistance Bands');
    expect(Equipment::Barbell->label())->toBe('Barbell');
    expect(Equipment::EZCurlBar->label())->toBe('EZ Curl Bar');
});
