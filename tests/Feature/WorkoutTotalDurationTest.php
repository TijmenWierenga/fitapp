<?php

use App\Enums\Workout\DurationType;
use App\Enums\Workout\StepKind;
use App\Models\Step;
use App\Models\User;
use App\Models\Workout;

test('it calculates the total duration of a workout correctly', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create();

    // 10 min warmup
    Step::factory()->for($workout)->create([
        'step_kind' => StepKind::Warmup,
        'duration_type' => DurationType::Time,
        'duration_value' => 600,
        'sort_order' => 1,
    ]);

    // Repeat 5x (2 min fast, 1 min slow)
    $repeat = Step::factory()->for($workout)->create([
        'step_kind' => StepKind::Repeat,
        'repeat_count' => 5,
        'sort_order' => 2,
    ]);

    Step::factory()->for($workout)->create([
        'parent_step_id' => $repeat->id,
        'duration_type' => DurationType::Time,
        'duration_value' => 120,
        'sort_order' => 1,
    ]);

    Step::factory()->for($workout)->create([
        'parent_step_id' => $repeat->id,
        'duration_type' => DurationType::Time,
        'duration_value' => 60,
        'sort_order' => 2,
    ]);

    // 5 min cooldown
    Step::factory()->for($workout)->create([
        'step_kind' => StepKind::Cooldown,
        'duration_type' => DurationType::Time,
        'duration_value' => 300,
        'sort_order' => 3,
    ]);

    // Total should be: 600 + (5 * (120 + 60)) + 300 = 600 + 900 + 300 = 1800 seconds (30 mins)
    expect($workout->totalDurationInSeconds())->toBe(1800);
});
