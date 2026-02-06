<?php

namespace Tests\Unit\Services;

use App\Enums\Workout\MuscleGroup;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutMuscleLoadSnapshot;
use App\Services\MuscleLoad\MuscleRecoveryService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MuscleRecoveryServiceTest extends TestCase
{
    use RefreshDatabase;

    private MuscleRecoveryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MuscleRecoveryService;
    }

    public function test_fresh_muscles_when_no_recent_snapshots(): void
    {
        $user = User::factory()->withTimezone('UTC')->create();

        $status = $this->service->getRecoveryStatus($user);

        foreach (MuscleGroup::cases() as $muscle) {
            $muscleStatus = $status[$muscle->value];
            $this->assertEquals(0.0, $muscleStatus['fatigue_score']);
            $this->assertEquals('fresh', $muscleStatus['status']);
            $this->assertTrue($muscleStatus['ready_for_heavy']);
            $this->assertEquals($muscle, $muscleStatus['muscle_group']);
        }
    }

    public function test_recovering_muscles_after_moderate_load_24h_ago(): void
    {
        $user = User::factory()->withTimezone('UTC')->create();
        $workout = Workout::factory()->for($user)->completed()->create();

        $now = CarbonImmutable::now();

        // Load of 50 -> 48h recovery window
        // 24h later -> remaining = max(0, 1 - 24/48) * 50 = 0.5 * 50 = 25
        WorkoutMuscleLoadSnapshot::create([
            'workout_id' => $workout->id,
            'muscle_group' => MuscleGroup::Quadriceps,
            'total_load' => 50.0,
            'source_breakdown' => [['description' => 'Squats', 'load' => 50.0]],
            'completed_at' => $now->subHours(24),
        ]);

        $status = $this->service->getRecoveryStatus($user, $now);

        $quads = $status[MuscleGroup::Quadriceps->value];
        $this->assertEqualsWithDelta(25.0, $quads['fatigue_score'], 0.5);
        $this->assertEquals('recovering', $quads['status']);
        $this->assertFalse($quads['ready_for_heavy']);
    }

    public function test_fatigued_muscles_after_high_load_recently(): void
    {
        $user = User::factory()->withTimezone('UTC')->create();
        $workout = Workout::factory()->for($user)->completed()->create();

        $now = CarbonImmutable::now();

        // Load of 80 -> 72h recovery window
        // 2h later -> remaining = max(0, 1 - 2/72) * 80 = ~0.972 * 80 = ~77.8
        WorkoutMuscleLoadSnapshot::create([
            'workout_id' => $workout->id,
            'muscle_group' => MuscleGroup::Quadriceps,
            'total_load' => 80.0,
            'source_breakdown' => [['description' => 'Heavy squats', 'load' => 80.0]],
            'completed_at' => $now->subHours(2),
        ]);

        $status = $this->service->getRecoveryStatus($user, $now);

        $quads = $status[MuscleGroup::Quadriceps->value];
        $this->assertGreaterThan(50.0, $quads['fatigue_score']);
        $this->assertEquals('fatigued', $quads['status']);
        $this->assertFalse($quads['ready_for_heavy']);
    }

    public function test_suggest_target_muscles_returns_sorted_by_fatigue(): void
    {
        $user = User::factory()->withTimezone('UTC')->create();
        $workout = Workout::factory()->for($user)->completed()->create();

        $now = CarbonImmutable::now();

        // Add high fatigue to quads
        WorkoutMuscleLoadSnapshot::create([
            'workout_id' => $workout->id,
            'muscle_group' => MuscleGroup::Quadriceps,
            'total_load' => 80.0,
            'source_breakdown' => [],
            'completed_at' => $now->subHours(2),
        ]);

        // Add moderate fatigue to chest
        WorkoutMuscleLoadSnapshot::create([
            'workout_id' => $workout->id,
            'muscle_group' => MuscleGroup::Chest,
            'total_load' => 40.0,
            'source_breakdown' => [],
            'completed_at' => $now->subHours(12),
        ]);

        $suggestions = $this->service->suggestTargetMuscles($user);

        // Should be sorted by fatigue_score ascending
        $scores = array_column($suggestions, 'fatigue_score');
        $sortedScores = $scores;
        sort($sortedScores);

        $this->assertEquals($sortedScores, $scores);

        // First entries should be the fresh muscles (0 fatigue)
        $this->assertEquals(0.0, $suggestions[0]['fatigue_score']);
    }
}
