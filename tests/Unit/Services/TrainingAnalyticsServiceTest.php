<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Models\Workout;
use App\Services\Training\TrainingAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainingAnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    private TrainingAnalyticsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TrainingAnalyticsService;
    }

    public function test_get_analytics_calculates_correctly_with_completed_workouts(): void
    {
        $user = User::factory()->withTimezone('UTC')->create();

        // Create completed workouts
        Workout::factory()->for($user)->completed()->count(3)->create([
            'completed_at' => now()->subDays(2),
            'rpe' => 7,
            'feeling' => 4,
        ]);

        $analytics = $this->service->getAnalytics($user, 4);

        // Verify the analytics are correctly calculated
        $this->assertEquals(4, $analytics['period_weeks']);
        $this->assertEquals(3, $analytics['total_completed']);
        $this->assertIsArray($analytics['workouts_per_week']);
    }

    public function test_get_analytics_handles_empty_collection(): void
    {
        $user = User::factory()->withTimezone('UTC')->create();

        $analytics = $this->service->getAnalytics($user, 4);

        // Verify empty state is handled correctly
        $this->assertEquals(0, $analytics['total_completed']);
        $this->assertEquals(0, $analytics['completion_rate']);
        $this->assertNull($analytics['average_rpe']);
        $this->assertNull($analytics['average_feeling']);
    }

    public function test_calculate_streak_counts_consecutive_days(): void
    {
        $user = User::factory()->withTimezone('UTC')->create();

        // Create workouts for the last 5 consecutive days
        for ($i = 0; $i < 5; $i++) {
            Workout::factory()->for($user)->completed()->create([
                'completed_at' => now()->subDays($i),
            ]);
        }

        $analytics = $this->service->getAnalytics($user, 4);

        $this->assertEquals(5, $analytics['current_streak_days']);
    }

    public function test_calculate_streak_stops_at_missing_day(): void
    {
        $user = User::factory()->withTimezone('UTC')->create();

        // Today and yesterday
        Workout::factory()->for($user)->completed()->create(['completed_at' => now()]);
        Workout::factory()->for($user)->completed()->create(['completed_at' => now()->subDay()]);

        // Skip day -2, create workout on day -3
        Workout::factory()->for($user)->completed()->create(['completed_at' => now()->subDays(3)]);

        $analytics = $this->service->getAnalytics($user, 4);

        // Streak should be 2 (today + yesterday), stopping before the gap
        $this->assertEquals(2, $analytics['current_streak_days']);
    }

    public function test_calculate_streak_handles_multiple_workouts_same_day(): void
    {
        $user = User::factory()->withTimezone('UTC')->create();

        // Create 3 workouts on the same day
        Workout::factory()->for($user)->completed()->count(3)->create([
            'completed_at' => now(),
        ]);

        $analytics = $this->service->getAnalytics($user, 4);

        // Should count as 1 day in the streak
        $this->assertEquals(1, $analytics['current_streak_days']);
    }

    public function test_calculate_streak_returns_zero_for_no_workouts(): void
    {
        $user = User::factory()->withTimezone('UTC')->create();

        $analytics = $this->service->getAnalytics($user, 4);

        $this->assertEquals(0, $analytics['current_streak_days']);
    }

    public function test_calculate_streak_returns_zero_when_no_recent_workouts(): void
    {
        $user = User::factory()->withTimezone('UTC')->create();

        // Create workout 10 days ago (streak is broken)
        Workout::factory()->for($user)->completed()->create([
            'completed_at' => now()->subDays(10),
        ]);

        $analytics = $this->service->getAnalytics($user, 4);

        $this->assertEquals(0, $analytics['current_streak_days']);
    }
}
