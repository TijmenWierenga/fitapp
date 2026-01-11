<?php

namespace Tests\Unit\Services;

use App\Enums\Workout\DurationType;
use App\Enums\Workout\Intensity;
use App\Enums\Workout\StepKind;
use App\Enums\Workout\TargetMode;
use App\Enums\Workout\TargetType;
use App\Models\Step;
use App\Models\Workout;
use App\Services\Workout\WorkoutEstimator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkoutEstimatorTest extends TestCase
{
    use RefreshDatabase;

    private WorkoutEstimator $estimator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->estimator = new WorkoutEstimator;
    }

    public function test_it_estimates_duration_for_distance_steps_based_on_intensity(): void
    {
        $workout = Workout::factory()->create();

        // 1km Active = 300s (5:00 min/km)
        Step::factory()->for($workout)->create([
            'duration_type' => DurationType::Distance,
            'duration_value' => 1000,
            'intensity' => Intensity::Active,
        ]);

        // 1km Warmup = 420s (7:00 min/km)
        Step::factory()->for($workout)->create([
            'duration_type' => DurationType::Distance,
            'duration_value' => 1000,
            'intensity' => Intensity::Warmup,
        ]);

        // 1km Rest = 540s (9:00 min/km)
        Step::factory()->for($workout)->create([
            'duration_type' => DurationType::Distance,
            'duration_value' => 1000,
            'intensity' => Intensity::Rest,
        ]);

        $this->assertEquals(300 + 420 + 540, $this->estimator->estimateDuration($workout));
    }

    public function test_it_estimates_distance_for_time_steps_based_on_intensity(): void
    {
        $workout = Workout::factory()->create();

        // 300s Active = 1000m
        Step::factory()->for($workout)->create([
            'duration_type' => DurationType::Time,
            'duration_value' => 300,
            'intensity' => Intensity::Active,
        ]);

        // 420s Warmup = 1000m
        Step::factory()->for($workout)->create([
            'duration_type' => DurationType::Time,
            'duration_value' => 420,
            'intensity' => Intensity::Warmup,
        ]);

        $this->assertEquals(2000, $this->estimator->estimateDistance($workout));
    }

    public function test_it_uses_pace_target_for_estimation_if_present(): void
    {
        $workout = Workout::factory()->create();

        // 1km at 4:00-4:30 min/km (240s-270s)
        // Average = 255s/km
        Step::factory()->for($workout)->create([
            'duration_type' => DurationType::Distance,
            'duration_value' => 1000,
            'target_type' => TargetType::Pace,
            'target_mode' => TargetMode::Range,
            'target_low' => 240,
            'target_high' => 270,
        ]);

        $this->assertEquals(255, $this->estimator->estimateDuration($workout));
    }

    public function test_it_handles_repeat_blocks_recursively(): void
    {
        $workout = Workout::factory()->create();

        $repeat = Step::factory()->for($workout)->create([
            'step_kind' => StepKind::Repeat,
            'repeat_count' => 3,
        ]);

        // 1km Active inside repeat (300s)
        Step::factory()->for($workout)->create([
            'parent_step_id' => $repeat->id,
            'duration_type' => DurationType::Distance,
            'duration_value' => 1000,
            'intensity' => Intensity::Active,
        ]);

        // 3 repeats * 300s = 900s
        $this->assertEquals(900, $this->estimator->estimateDuration($workout));
        // 3 repeats * 1000m = 3000m
        $this->assertEquals(3000, $this->estimator->estimateDistance($workout));
    }

    public function test_it_combines_explicit_and_estimated_values(): void
    {
        $workout = Workout::factory()->create();

        // Explicit 15 min Warmup (900s)
        // Estimated distance at 7:00 min/km (420s/km) = 900 / 420 * 1000 = 2143m (rounded)
        Step::factory()->for($workout)->create([
            'duration_type' => DurationType::Time,
            'duration_value' => 900,
            'intensity' => Intensity::Warmup,
        ]);

        // Explicit 7 km Run (7000m)
        // Estimated duration at 5:00 min/km (300s/km) = 7 * 300 = 2100s
        Step::factory()->for($workout)->create([
            'duration_type' => DurationType::Distance,
            'duration_value' => 7000,
            'intensity' => Intensity::Active,
        ]);

        $this->assertEquals(900 + 2100, $this->estimator->estimateDuration($workout));
        $this->assertEquals(2143 + 7000, $this->estimator->estimateDistance($workout));
    }
}
