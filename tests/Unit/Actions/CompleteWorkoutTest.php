<?php

namespace Tests\Unit\Actions;

use App\Actions\Workout\CompleteWorkout;
use App\Enums\Workout\Activity;
use App\Enums\Workout\IntervalIntensity;
use App\Enums\Workout\MuscleGroup;
use App\Enums\Workout\MuscleRole;
use App\Models\ActivityMuscleLoad;
use App\Models\IntervalBlock;
use App\Models\Workout;
use App\Models\WorkoutBlock;
use App\Models\WorkoutMuscleLoadSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompleteWorkoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_muscle_load_snapshots_on_completion(): void
    {
        $workout = Workout::factory()->create(['activity' => Activity::Run]);

        // Create activity muscle loads for running
        ActivityMuscleLoad::create([
            'activity' => Activity::Run,
            'muscle_group' => MuscleGroup::Quadriceps,
            'role' => MuscleRole::Primary,
            'load_factor' => 1.0,
        ]);

        ActivityMuscleLoad::create([
            'activity' => Activity::Run,
            'muscle_group' => MuscleGroup::Calves,
            'role' => MuscleRole::Secondary,
            'load_factor' => 0.5,
        ]);

        // Create an interval block
        $intervalBlock = IntervalBlock::factory()->create([
            'duration_seconds' => 600,
            'intensity' => IntervalIntensity::Moderate,
        ]);

        WorkoutBlock::factory()->interval()->create([
            'workout_id' => $workout->id,
            'position' => 0,
            'blockable_type' => 'interval_block',
            'blockable_id' => $intervalBlock->id,
        ]);

        $action = app(CompleteWorkout::class);
        $action->execute($workout, rpe: 7, feeling: 4);

        // Verify snapshots were created
        $snapshots = WorkoutMuscleLoadSnapshot::where('workout_id', $workout->id)->get();
        $this->assertCount(2, $snapshots);

        $quadSnapshot = $snapshots->firstWhere('muscle_group', MuscleGroup::Quadriceps);
        $this->assertNotNull($quadSnapshot);
        $this->assertGreaterThan(0, $quadSnapshot->total_load);
        $this->assertNotNull($quadSnapshot->completed_at);

        $calfSnapshot = $snapshots->firstWhere('muscle_group', MuscleGroup::Calves);
        $this->assertNotNull($calfSnapshot);
        $this->assertGreaterThan(0, $calfSnapshot->total_load);
    }

    public function test_it_sets_completion_data_on_workout(): void
    {
        $workout = Workout::factory()->create(['activity' => Activity::Run]);

        $action = app(CompleteWorkout::class);
        $action->execute($workout, rpe: 8, feeling: 5);

        $workout->refresh();

        $this->assertNotNull($workout->completed_at);
        $this->assertEquals(8, $workout->rpe);
        $this->assertEquals(5, $workout->feeling);
    }

    public function test_it_handles_workout_with_no_blocks(): void
    {
        $workout = Workout::factory()->create();

        $action = app(CompleteWorkout::class);
        $action->execute($workout, rpe: 6, feeling: 3);

        $workout->refresh();

        $this->assertNotNull($workout->completed_at);
        $this->assertEquals(6, $workout->rpe);
        $this->assertEquals(3, $workout->feeling);

        $snapshots = WorkoutMuscleLoadSnapshot::where('workout_id', $workout->id)->count();
        $this->assertEquals(0, $snapshots);
    }
}
