<?php

use App\Enums\BodyPart;
use App\Models\Exercise;
use App\Models\MuscleGroup;

it('has exercises relationship', function (): void {
    $muscleGroup = MuscleGroup::factory()->create();
    $exercise = Exercise::factory()->create();

    $muscleGroup->exercises()->attach($exercise, ['load_factor' => 1.0]);

    expect($muscleGroup->exercises)->toHaveCount(1);
    expect($muscleGroup->exercises->first()->id)->toBe($exercise->id);
});

it('scopes by body part', function (): void {
    MuscleGroup::factory()->create(['body_part' => BodyPart::Chest]);
    MuscleGroup::factory()->create(['body_part' => BodyPart::Chest]);
    MuscleGroup::factory()->create(['body_part' => BodyPart::Shoulder]);

    expect(MuscleGroup::forBodyPart(BodyPart::Chest)->count())->toBe(2);
    expect(MuscleGroup::forBodyPart(BodyPart::Shoulder)->count())->toBe(1);
});

it('casts body_part to enum', function (): void {
    $muscleGroup = MuscleGroup::factory()->create(['body_part' => BodyPart::Core]);

    expect($muscleGroup->body_part)->toBe(BodyPart::Core);
});
