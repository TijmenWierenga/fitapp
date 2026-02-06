<?php

namespace Tests\Unit\Services;

use App\Enums\Workout\Activity;
use App\Enums\Workout\IntervalIntensity;
use App\Enums\Workout\MuscleGroup;
use App\Enums\Workout\MuscleRole;
use App\Models\ActivityMuscleLoad;
use App\Models\Exercise;
use App\Models\ExerciseEntry;
use App\Models\ExerciseGroup;
use App\Models\ExerciseMuscleLoad;
use App\Models\IntervalBlock;
use App\Models\Workout;
use App\Models\WorkoutBlock;
use App\Services\MuscleLoad\MuscleLoadCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MuscleLoadCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private MuscleLoadCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new MuscleLoadCalculator;
    }

    public function test_it_calculates_muscle_load_for_cardio_interval_workout(): void
    {
        $workout = Workout::factory()->create(['activity' => Activity::Run]);

        // Create a 10-minute easy interval block
        $intervalBlock = IntervalBlock::factory()->create([
            'duration_seconds' => 600,
            'intensity' => IntervalIntensity::Easy,
        ]);

        WorkoutBlock::factory()->interval()->create([
            'workout_id' => $workout->id,
            'position' => 0,
            'blockable_type' => 'interval_block',
            'blockable_id' => $intervalBlock->id,
        ]);

        // Create activity muscle load records for running
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

        $summary = $this->calculator->calculate($workout);

        // 10 min * 0.3 (easy) * 1.0 (load_factor) * 1 (repeat) = 3.0
        $quads = $summary->forMuscle(MuscleGroup::Quadriceps);
        $this->assertEqualsWithDelta(3.0, $quads['total'], 0.01);

        // 10 min * 0.3 (easy) * 0.5 (load_factor) * 1 (repeat) = 1.5
        $calves = $summary->forMuscle(MuscleGroup::Calves);
        $this->assertEqualsWithDelta(1.5, $calves['total'], 0.01);
    }

    public function test_it_calculates_muscle_load_for_strength_workout(): void
    {
        $workout = Workout::factory()->strength()->create();

        $exercise = Exercise::factory()->create(['name' => 'Barbell Squat']);

        ExerciseMuscleLoad::create([
            'exercise_id' => $exercise->id,
            'muscle_group' => MuscleGroup::Quadriceps,
            'role' => MuscleRole::Primary,
            'load_factor' => 1.0,
        ]);

        ExerciseMuscleLoad::create([
            'exercise_id' => $exercise->id,
            'muscle_group' => MuscleGroup::Glutes,
            'role' => MuscleRole::Secondary,
            'load_factor' => 0.7,
        ]);

        $exerciseGroup = ExerciseGroup::factory()->create([
            'rounds' => 1,
        ]);

        ExerciseEntry::factory()->create([
            'exercise_group_id' => $exerciseGroup->id,
            'exercise_id' => $exercise->id,
            'sets' => 3,
            'reps' => 10,
            'rpe_target' => 8,
        ]);

        WorkoutBlock::factory()->exerciseGroup()->create([
            'workout_id' => $workout->id,
            'position' => 0,
            'blockable_type' => 'exercise_group',
            'blockable_id' => $exerciseGroup->id,
        ]);

        $summary = $this->calculator->calculate($workout);

        // volume = 3 sets * 10 reps * 1 round = 30
        // effort = 8/10 = 0.8
        // quads: 30 * 0.8 * 1.0 * 1 = 24.0
        $quads = $summary->forMuscle(MuscleGroup::Quadriceps);
        $this->assertEqualsWithDelta(24.0, $quads['total'], 0.01);

        // glutes: 30 * 0.8 * 0.7 * 1 = 16.8
        $glutes = $summary->forMuscle(MuscleGroup::Glutes);
        $this->assertEqualsWithDelta(16.8, $glutes['total'], 0.01);
    }

    public function test_it_applies_nested_repeat_multiplier(): void
    {
        $workout = Workout::factory()->create(['activity' => Activity::Run]);

        ActivityMuscleLoad::create([
            'activity' => Activity::Run,
            'muscle_group' => MuscleGroup::Quadriceps,
            'role' => MuscleRole::Primary,
            'load_factor' => 1.0,
        ]);

        $intervalBlock = IntervalBlock::factory()->create([
            'duration_seconds' => 300,
            'intensity' => IntervalIntensity::Moderate,
        ]);

        // Create a group block with repeat_count=3
        $groupBlock = WorkoutBlock::factory()->group()->create([
            'workout_id' => $workout->id,
            'position' => 0,
            'repeat_count' => 3,
        ]);

        // Interval block inside the group
        WorkoutBlock::factory()->interval()->create([
            'workout_id' => $workout->id,
            'parent_id' => $groupBlock->id,
            'position' => 0,
            'blockable_type' => 'interval_block',
            'blockable_id' => $intervalBlock->id,
        ]);

        $summary = $this->calculator->calculate($workout);

        // 5 min * 0.5 (moderate) * 1.0 (load_factor) * 3 (group repeat) * 1 (child repeat) = 7.5
        $quads = $summary->forMuscle(MuscleGroup::Quadriceps);
        $this->assertEqualsWithDelta(7.5, $quads['total'], 0.01);
    }

    public function test_it_returns_empty_summary_for_workout_with_no_blocks(): void
    {
        $workout = Workout::factory()->create();

        $summary = $this->calculator->calculate($workout);

        $this->assertEmpty($summary->all());
        $this->assertEquals(0.0, $summary->totalLoad());
    }

    public function test_it_calculates_combined_loads_for_mixed_workout(): void
    {
        $workout = Workout::factory()->create(['activity' => Activity::Run]);

        // Activity muscle load for running
        ActivityMuscleLoad::create([
            'activity' => Activity::Run,
            'muscle_group' => MuscleGroup::Quadriceps,
            'role' => MuscleRole::Primary,
            'load_factor' => 1.0,
        ]);

        // Interval block: 5 min moderate run
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

        // Exercise group block with an exercise that also targets quads
        $exercise = Exercise::factory()->create(['name' => 'Lunges']);

        ExerciseMuscleLoad::create([
            'exercise_id' => $exercise->id,
            'muscle_group' => MuscleGroup::Quadriceps,
            'role' => MuscleRole::Primary,
            'load_factor' => 1.0,
        ]);

        $exerciseGroup = ExerciseGroup::factory()->create(['rounds' => 1]);

        ExerciseEntry::factory()->create([
            'exercise_group_id' => $exerciseGroup->id,
            'exercise_id' => $exercise->id,
            'sets' => 3,
            'reps' => 10,
            'rpe_target' => null, // defaults to 0.6
        ]);

        WorkoutBlock::factory()->exerciseGroup()->create([
            'workout_id' => $workout->id,
            'position' => 1,
            'blockable_type' => 'exercise_group',
            'blockable_id' => $exerciseGroup->id,
        ]);

        $summary = $this->calculator->calculate($workout);

        // Interval: 5 min * 0.5 * 1.0 * 1 = 2.5
        // Exercise: 30 volume * 0.6 effort * 1.0 * 1 = 18.0
        // Total quads: 2.5 + 18.0 = 20.5
        $quads = $summary->forMuscle(MuscleGroup::Quadriceps);
        $this->assertEqualsWithDelta(20.5, $quads['total'], 0.01);
        $this->assertCount(2, $quads['sources']);
    }
}
