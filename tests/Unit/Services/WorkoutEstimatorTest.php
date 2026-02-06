<?php

namespace Tests\Unit\Services;

use App\Enums\Workout\IntervalIntensity;
use App\Models\ExerciseEntry;
use App\Models\ExerciseGroup;
use App\Models\IntervalBlock;
use App\Models\RestBlock;
use App\Models\Workout;
use App\Models\WorkoutBlock;
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

    public function test_it_estimates_duration_from_time_based_interval(): void
    {
        $workout = Workout::factory()->create();

        $intervalBlock = IntervalBlock::factory()->create([
            'duration_seconds' => 300,
            'intensity' => IntervalIntensity::Moderate,
        ]);

        WorkoutBlock::factory()->interval()->create([
            'workout_id' => $workout->id,
            'position' => 0,
            'blockable_type' => 'interval_block',
            'blockable_id' => $intervalBlock->id,
        ]);

        $this->assertEquals(300, $this->estimator->estimateDuration($workout));
    }

    public function test_it_estimates_duration_from_distance_based_interval(): void
    {
        $workout = Workout::factory()->create();

        // 1km at moderate intensity (330s/km default pace)
        $intervalBlock = IntervalBlock::factory()->distanceBased(1000)->create([
            'intensity' => IntervalIntensity::Moderate,
        ]);

        WorkoutBlock::factory()->interval()->create([
            'workout_id' => $workout->id,
            'position' => 0,
            'blockable_type' => 'interval_block',
            'blockable_id' => $intervalBlock->id,
        ]);

        $this->assertEquals(330, $this->estimator->estimateDuration($workout));
    }

    public function test_it_estimates_distance_from_distance_based_interval(): void
    {
        $workout = Workout::factory()->create();

        $intervalBlock = IntervalBlock::factory()->distanceBased(1000)->create([
            'intensity' => IntervalIntensity::Moderate,
        ]);

        WorkoutBlock::factory()->interval()->create([
            'workout_id' => $workout->id,
            'position' => 0,
            'blockable_type' => 'interval_block',
            'blockable_id' => $intervalBlock->id,
        ]);

        $this->assertEquals(1000, $this->estimator->estimateDistance($workout));
    }

    public function test_it_estimates_exercise_group_duration(): void
    {
        $workout = Workout::factory()->create();

        $exerciseGroup = ExerciseGroup::factory()->create([
            'rounds' => 1,
            'rest_between_rounds_seconds' => null,
        ]);

        // 3 sets * 10 reps * 3s = 90s + rest: 90s * (3-1) = 180s = 270s total
        ExerciseEntry::factory()->create([
            'exercise_group_id' => $exerciseGroup->id,
            'sets' => 3,
            'reps' => 10,
            'rest_between_sets_seconds' => 90,
        ]);

        WorkoutBlock::factory()->exerciseGroup()->create([
            'workout_id' => $workout->id,
            'position' => 0,
            'blockable_type' => 'exercise_group',
            'blockable_id' => $exerciseGroup->id,
        ]);

        $this->assertEquals(270, $this->estimator->estimateDuration($workout));
    }

    public function test_it_estimates_rest_block_duration(): void
    {
        $workout = Workout::factory()->create();

        $restBlock = RestBlock::factory()->create(['duration_seconds' => 60]);

        WorkoutBlock::factory()->rest()->create([
            'workout_id' => $workout->id,
            'position' => 0,
            'blockable_type' => 'rest_block',
            'blockable_id' => $restBlock->id,
        ]);

        $this->assertEquals(60, $this->estimator->estimateDuration($workout));
    }

    public function test_it_applies_repeat_count_and_rest_between_repeats(): void
    {
        $workout = Workout::factory()->create();

        $intervalBlock = IntervalBlock::factory()->create([
            'duration_seconds' => 300,
            'intensity' => IntervalIntensity::Threshold,
        ]);

        // Group with repeat_count=3 and 60s rest between repeats
        $groupBlock = WorkoutBlock::factory()->group()->create([
            'workout_id' => $workout->id,
            'position' => 0,
            'repeat_count' => 3,
            'rest_between_repeats_seconds' => 60,
        ]);

        WorkoutBlock::factory()->interval()->create([
            'workout_id' => $workout->id,
            'parent_id' => $groupBlock->id,
            'position' => 0,
            'blockable_type' => 'interval_block',
            'blockable_id' => $intervalBlock->id,
        ]);

        // Group duration: children sum = 300s
        // Total: 300 * 3 (repeats) + 60 * 2 (rest between) = 900 + 120 = 1020
        $this->assertEquals(1020, $this->estimator->estimateDuration($workout));
    }

    public function test_it_returns_zero_for_empty_workout(): void
    {
        $workout = Workout::factory()->create();

        $this->assertEquals(0, $this->estimator->estimateDuration($workout));
        $this->assertEquals(0, $this->estimator->estimateDistance($workout));
    }

    public function test_it_combines_mixed_block_types(): void
    {
        $workout = Workout::factory()->create();

        // Interval: 300s
        $intervalBlock = IntervalBlock::factory()->create([
            'duration_seconds' => 300,
            'intensity' => IntervalIntensity::Moderate,
        ]);

        WorkoutBlock::factory()->interval()->create([
            'workout_id' => $workout->id,
            'position' => 0,
            'blockable_type' => 'interval_block',
            'blockable_id' => $intervalBlock->id,
        ]);

        // Exercise group: 3 sets * 10 reps * 3s = 90s + 90 * 2 rest = 270s
        $exerciseGroup = ExerciseGroup::factory()->create([
            'rounds' => 1,
        ]);

        ExerciseEntry::factory()->create([
            'exercise_group_id' => $exerciseGroup->id,
            'sets' => 3,
            'reps' => 10,
            'rest_between_sets_seconds' => 90,
        ]);

        WorkoutBlock::factory()->exerciseGroup()->create([
            'workout_id' => $workout->id,
            'position' => 1,
            'blockable_type' => 'exercise_group',
            'blockable_id' => $exerciseGroup->id,
        ]);

        // Rest: 60s
        $restBlock = RestBlock::factory()->create(['duration_seconds' => 60]);

        WorkoutBlock::factory()->rest()->create([
            'workout_id' => $workout->id,
            'position' => 2,
            'blockable_type' => 'rest_block',
            'blockable_id' => $restBlock->id,
        ]);

        // Total: 300 + 270 + 60 = 630s
        $this->assertEquals(630, $this->estimator->estimateDuration($workout));
    }

    public function test_it_uses_target_pace_when_set(): void
    {
        $workout = Workout::factory()->create();

        // 1km with explicit target pace of 300s/km
        $intervalBlock = IntervalBlock::factory()->distanceBased(1000)->create([
            'intensity' => IntervalIntensity::Moderate,
            'target_pace_seconds_per_km' => 300,
        ]);

        WorkoutBlock::factory()->interval()->create([
            'workout_id' => $workout->id,
            'position' => 0,
            'blockable_type' => 'interval_block',
            'blockable_id' => $intervalBlock->id,
        ]);

        // Should use 300s/km target pace, not 330s/km default moderate pace
        $this->assertEquals(300, $this->estimator->estimateDuration($workout));
    }

    public function test_it_estimates_timed_exercise_entries(): void
    {
        $workout = Workout::factory()->create();

        $exerciseGroup = ExerciseGroup::factory()->create([
            'rounds' => 1,
        ]);

        // Timed entry: 3 sets * 30s = 90s + 60 * 2 rest = 210s
        ExerciseEntry::factory()->timed(30)->create([
            'exercise_group_id' => $exerciseGroup->id,
            'sets' => 3,
            'rest_between_sets_seconds' => 60,
        ]);

        WorkoutBlock::factory()->exerciseGroup()->create([
            'workout_id' => $workout->id,
            'position' => 0,
            'blockable_type' => 'exercise_group',
            'blockable_id' => $exerciseGroup->id,
        ]);

        $this->assertEquals(210, $this->estimator->estimateDuration($workout));
    }

    public function test_it_applies_exercise_group_rounds(): void
    {
        $workout = Workout::factory()->create();

        $exerciseGroup = ExerciseGroup::factory()->create([
            'rounds' => 3,
            'rest_between_rounds_seconds' => 120,
        ]);

        // 2 sets * 8 reps * 3s = 48s + 60 * 1 rest = 108s per round
        ExerciseEntry::factory()->create([
            'exercise_group_id' => $exerciseGroup->id,
            'sets' => 2,
            'reps' => 8,
            'rest_between_sets_seconds' => 60,
        ]);

        WorkoutBlock::factory()->exerciseGroup()->create([
            'workout_id' => $workout->id,
            'position' => 0,
            'blockable_type' => 'exercise_group',
            'blockable_id' => $exerciseGroup->id,
        ]);

        // 108 * 3 rounds + 120 * 2 rest_between_rounds = 324 + 240 = 564s
        $this->assertEquals(564, $this->estimator->estimateDuration($workout));
    }

    public function test_it_accumulates_distance_through_repeat_groups(): void
    {
        $workout = Workout::factory()->create();

        $intervalBlock = IntervalBlock::factory()->distanceBased(400)->create([
            'intensity' => IntervalIntensity::Threshold,
        ]);

        $groupBlock = WorkoutBlock::factory()->group()->create([
            'workout_id' => $workout->id,
            'position' => 0,
            'repeat_count' => 5,
        ]);

        WorkoutBlock::factory()->interval()->create([
            'workout_id' => $workout->id,
            'parent_id' => $groupBlock->id,
            'position' => 0,
            'blockable_type' => 'interval_block',
            'blockable_id' => $intervalBlock->id,
        ]);

        // 400m * 5 repeats = 2000m
        $this->assertEquals(2000, $this->estimator->estimateDistance($workout));
    }
}
