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

    public function test_get_analytics_accepts_eloquent_collection_internally(): void
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
        $user = User::factory()->create();

        $analytics = $this->service->getAnalytics($user, 4);

        // Verify empty state is handled correctly
        $this->assertEquals(0, $analytics['total_completed']);
        $this->assertEquals(0, $analytics['completion_rate']);
        $this->assertNull($analytics['average_rpe']);
        $this->assertNull($analytics['average_feeling']);
    }
}
