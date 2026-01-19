<?php

namespace Tests\Unit\Services;

use App\Enums\Workout\DurationType;
use App\Enums\Workout\Intensity;
use App\Enums\Workout\IntensityLevel;
use App\Enums\Workout\StepKind;
use App\Enums\Workout\TargetMode;
use App\Enums\Workout\TargetType;
use App\Models\Step;
use App\Models\Workout;
use App\Services\Workout\IntensityCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntensityCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private IntensityCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new IntensityCalculator;
    }

    public function test_it_returns_default_score_for_non_running_workouts(): void
    {
        $workout = Workout::factory()->create(['sport' => 'cycling']);

        $this->assertEquals(50, $this->calculator->calculate($workout));
        $this->assertEquals(IntensityLevel::Medium, $this->calculator->level($workout));
    }

    public function test_it_calculates_duration_factor_based_on_estimated_duration(): void
    {
        // 100 minutes = 40 points (max)
        // We need 100 minutes of Active running at 5:00/km
        // 100 min = 6000s => 20km at 300s/km = 6000s
        $workout = Workout::factory()->create(['sport' => 'running']);

        Step::factory()->for($workout)->create([
            'duration_type' => DurationType::Distance,
            'duration_value' => 20000, // 20km
            'intensity' => Intensity::Active,
        ]);

        $score = $this->calculator->calculate($workout);
        // Duration factor = 40, Intensity factor = 40 (all Active), Target factor = 0 (no target)
        $this->assertEquals(80, $score);
    }

    public function test_it_calculates_intensity_factor_based_on_weighted_duration(): void
    {
        $workout = Workout::factory()->create(['sport' => 'running']);

        // 10 min Warmup (600s at 7:00/km)
        Step::factory()->for($workout)->create([
            'duration_type' => DurationType::Time,
            'duration_value' => 600,
            'intensity' => Intensity::Warmup,
        ]);

        // 30 min Active (1800s)
        Step::factory()->for($workout)->create([
            'duration_type' => DurationType::Time,
            'duration_value' => 1800,
            'intensity' => Intensity::Active,
        ]);

        // 10 min Cooldown (600s)
        Step::factory()->for($workout)->create([
            'duration_type' => DurationType::Time,
            'duration_value' => 600,
            'intensity' => Intensity::Cooldown,
        ]);

        // Total 50 min = 3000s
        // Duration factor = 50 / 2.5 = 20
        // Weighted: (600 * 0.3 + 1800 * 1.0 + 600 * 0.3) / 3000 = (180 + 1800 + 180) / 3000 = 0.72
        // Intensity factor = 0.72 * 40 = 28.8

        $score = $this->calculator->calculate($workout);
        $this->assertEquals(49, $score); // 20 + 28.8 + 0 rounded = 49
    }

    public function test_it_calculates_target_factor_from_heart_rate_zones(): void
    {
        $workout = Workout::factory()->create(['sport' => 'running']);

        // 30 min Active in Zone 4 (300s/km baseline)
        Step::factory()->for($workout)->create([
            'duration_type' => DurationType::Time,
            'duration_value' => 1800,
            'intensity' => Intensity::Active,
            'target_type' => TargetType::HeartRate,
            'target_mode' => TargetMode::Zone,
            'target_zone' => 4,
        ]);

        $score = $this->calculator->calculate($workout);
        // Duration = 30/2.5 = 12
        // Intensity = 1.0 * 40 = 40 (all Active)
        // Target = 10 (Zone 4)
        $this->assertEquals(62, $score);
    }

    public function test_it_calculates_target_factor_from_pace_ranges(): void
    {
        $workout = Workout::factory()->create(['sport' => 'running']);

        // 30 min Active at fast pace (250-270 s/km avg = 260s/km)
        Step::factory()->for($workout)->create([
            'duration_type' => DurationType::Time,
            'duration_value' => 1800,
            'intensity' => Intensity::Active,
            'target_type' => TargetType::Pace,
            'target_mode' => TargetMode::Range,
            'target_low' => 250,
            'target_high' => 270,
        ]);

        $score = $this->calculator->calculate($workout);
        // Duration = 30/2.5 = 12
        // Intensity = 1.0 * 40 = 40 (all Active)
        // Target = 20 (avg pace < 270s/km)
        $this->assertEquals(72, $score);
    }

    public function test_it_handles_repeat_blocks(): void
    {
        $workout = Workout::factory()->create(['sport' => 'running']);

        // 10 min Warmup
        Step::factory()->for($workout)->create([
            'duration_type' => DurationType::Time,
            'duration_value' => 600,
            'intensity' => Intensity::Warmup,
        ]);

        // 5x (1km Active + 2min Rest)
        $repeat = Step::factory()->for($workout)->create([
            'step_kind' => StepKind::Repeat,
            'repeat_count' => 5,
        ]);

        // 1km Active (300s at 5:00/km)
        Step::factory()->for($workout)->create([
            'parent_step_id' => $repeat->id,
            'duration_type' => DurationType::Distance,
            'duration_value' => 1000,
            'intensity' => Intensity::Active,
        ]);

        // 2min Rest (120s)
        Step::factory()->for($workout)->create([
            'parent_step_id' => $repeat->id,
            'duration_type' => DurationType::Time,
            'duration_value' => 120,
            'intensity' => Intensity::Rest,
        ]);

        // 10 min Cooldown
        Step::factory()->for($workout)->create([
            'duration_type' => DurationType::Time,
            'duration_value' => 600,
            'intensity' => Intensity::Cooldown,
        ]);

        // Total duration = 600 + 5*(300+120) + 600 = 600 + 2100 + 600 = 3300s = 55 min
        // Duration factor = 55 / 2.5 = 22
        // Weighted durations: 600*0.3 + 5*(300*1.0 + 120*0.1) + 600*0.3 = 180 + 5*(300+12) + 180 = 180 + 1560 + 180 = 1920
        // Total duration: 3300
        // Intensity ratio: 1920/3300 = 0.582
        // Intensity factor: 0.582 * 40 = 23.3
        // Target = 0 (no target)

        $score = $this->calculator->calculate($workout);
        $this->assertEquals(45, $score); // 22 + 23 rounded
    }

    public function test_it_uses_max_target_score_from_multiple_active_steps(): void
    {
        $workout = Workout::factory()->create(['sport' => 'running']);

        // 15 min Active at Zone 3 (target = 5)
        Step::factory()->for($workout)->create([
            'duration_type' => DurationType::Time,
            'duration_value' => 900,
            'intensity' => Intensity::Active,
            'target_type' => TargetType::HeartRate,
            'target_mode' => TargetMode::Zone,
            'target_zone' => 3,
        ]);

        // 15 min Active at Zone 5 (target = 20)
        Step::factory()->for($workout)->create([
            'duration_type' => DurationType::Time,
            'duration_value' => 900,
            'intensity' => Intensity::Active,
            'target_type' => TargetType::HeartRate,
            'target_mode' => TargetMode::Zone,
            'target_zone' => 5,
        ]);

        $score = $this->calculator->calculate($workout);
        // Duration = 30/2.5 = 12
        // Intensity = 1.0 * 40 = 40 (all Active)
        // Target = 20 (max of 5 and 20)
        $this->assertEquals(72, $score);
    }

    public function test_it_caps_score_at_100(): void
    {
        $workout = Workout::factory()->create(['sport' => 'running']);

        // 120 min Active at Zone 5
        Step::factory()->for($workout)->create([
            'duration_type' => DurationType::Time,
            'duration_value' => 7200, // 120 min
            'intensity' => Intensity::Active,
            'target_type' => TargetType::HeartRate,
            'target_mode' => TargetMode::Zone,
            'target_zone' => 5,
        ]);

        // Duration factor = min(40, 120/2.5) = 40
        // Intensity factor = 40 (all Active)
        // Target factor = 20 (Zone 5)
        // Total = 100 (capped)

        $score = $this->calculator->calculate($workout);
        $this->assertEquals(100, $score);
        $this->assertEquals(IntensityLevel::VeryHigh, $this->calculator->level($workout));
    }

    public function test_it_returns_recovery_level_for_low_intensity_workout(): void
    {
        $workout = Workout::factory()->create(['sport' => 'running']);

        // 20 min easy jog (mostly warmup intensity)
        Step::factory()->for($workout)->create([
            'duration_type' => DurationType::Time,
            'duration_value' => 1200, // 20 min
            'intensity' => Intensity::Warmup,
        ]);

        $score = $this->calculator->calculate($workout);
        // Duration = 20/2.5 = 8
        // Intensity = 0.3 * 40 = 12 (warmup)
        // Target = 0
        $this->assertEquals(20, $score);
        $this->assertEquals(IntensityLevel::Recovery, $this->calculator->level($workout));
    }

    public function test_it_returns_correct_intensity_level_for_score_boundaries(): void
    {
        $this->assertEquals(IntensityLevel::Recovery, IntensityLevel::fromScore(0));
        $this->assertEquals(IntensityLevel::Recovery, IntensityLevel::fromScore(25));
        $this->assertEquals(IntensityLevel::Low, IntensityLevel::fromScore(26));
        $this->assertEquals(IntensityLevel::Low, IntensityLevel::fromScore(45));
        $this->assertEquals(IntensityLevel::Medium, IntensityLevel::fromScore(46));
        $this->assertEquals(IntensityLevel::Medium, IntensityLevel::fromScore(65));
        $this->assertEquals(IntensityLevel::High, IntensityLevel::fromScore(66));
        $this->assertEquals(IntensityLevel::High, IntensityLevel::fromScore(85));
        $this->assertEquals(IntensityLevel::VeryHigh, IntensityLevel::fromScore(86));
        $this->assertEquals(IntensityLevel::VeryHigh, IntensityLevel::fromScore(100));
    }
}
