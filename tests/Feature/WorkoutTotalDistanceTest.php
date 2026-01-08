<?php

use App\Enums\Workout\DurationType;
use App\Enums\Workout\StepKind;
use App\Models\Step;
use App\Models\User;
use App\Models\Workout;

test('it calculates the total distance of a workout correctly', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create();

    // 1km warmup
    Step::factory()->for($workout)->create([
        'step_kind' => StepKind::Warmup,
        'duration_type' => DurationType::Distance,
        'duration_value' => 1000,
        'sort_order' => 1,
    ]);

    // Repeat 3x (500m fast, 200m slow)
    $repeat = Step::factory()->for($workout)->create([
        'step_kind' => StepKind::Repeat,
        'repeat_count' => 3,
        'sort_order' => 2,
    ]);

    Step::factory()->for($workout)->create([
        'parent_step_id' => $repeat->id,
        'duration_type' => DurationType::Distance,
        'duration_value' => 500,
        'sort_order' => 1,
    ]);

    Step::factory()->for($workout)->create([
        'parent_step_id' => $repeat->id,
        'duration_type' => DurationType::Distance,
        'duration_value' => 200,
        'sort_order' => 2,
    ]);

    // 1km cooldown
    Step::factory()->for($workout)->create([
        'step_kind' => StepKind::Cooldown,
        'duration_type' => DurationType::Distance,
        'duration_value' => 1000,
        'sort_order' => 3,
    ]);

    // Total should be: 1000 + (3 * (500 + 200)) + 1000 = 1000 + 2100 + 1000 = 4100
    expect($workout->totalDistanceInMeters())->toBe(4100);
});
