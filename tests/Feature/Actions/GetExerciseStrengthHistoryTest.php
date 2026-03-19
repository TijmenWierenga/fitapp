<?php

use App\Actions\GetExerciseStrengthHistory;
use App\Domain\Workload\Enums\HistoryRange;
use App\Models\Block;
use App\Models\BlockExercise;
use App\Models\Exercise;
use App\Models\Section;
use App\Models\StrengthExercise;
use App\Models\User;
use App\Models\Workout;
use Carbon\CarbonImmutable;

beforeEach(function (): void {
    $this->user = User::factory()->withTimezone('UTC')->create();
    $this->exercise = Exercise::factory()->create(['name' => 'Bench Press']);
    $this->action = app(GetExerciseStrengthHistory::class);
    $this->now = CarbonImmutable::parse('2026-02-14 12:00:00');
});

function createWorkoutWithStrength(
    User $user,
    Exercise $exercise,
    CarbonImmutable $completedAt,
    float $weight,
    int $reps = 5,
    int $sets = 3,
    bool $completed = true,
): Workout {
    $workout = Workout::factory()->create([
        'user_id' => $user->id,
        'completed_at' => $completed ? $completedAt : null,
        'scheduled_at' => $completedAt->subHour(),
    ]);

    $section = Section::factory()->create(['workout_id' => $workout->id]);
    $block = Block::factory()->create(['section_id' => $section->id]);

    $strength = StrengthExercise::factory()->create([
        'target_sets' => $sets,
        'target_reps_max' => $reps,
        'target_weight' => $weight,
    ]);

    BlockExercise::factory()->create([
        'block_id' => $block->id,
        'exercise_id' => $exercise->id,
        'exerciseable_type' => $strength->getMorphClass(),
        'exerciseable_id' => $strength->id,
    ]);

    return $workout;
}

it('returns history for exercise across multiple workouts', function (): void {
    createWorkoutWithStrength($this->user, $this->exercise, $this->now->subDays(7), weight: 80.0);
    createWorkoutWithStrength($this->user, $this->exercise, $this->now->subDays(3), weight: 85.0);
    createWorkoutWithStrength($this->user, $this->exercise, $this->now->subDays(1), weight: 90.0);

    $result = $this->action->execute($this->user, $this->exercise->id, HistoryRange::ThreeMonths, $this->now);

    expect($result->exerciseId)->toBe($this->exercise->id);
    expect($result->exerciseName)->toBe('Bench Press');
    expect($result->points)->toHaveCount(3);
    expect($result->points[0]->maxWeight)->toBe(80.0);
    expect($result->points[2]->maxWeight)->toBe(90.0);
});

it('respects time range and excludes out-of-range data', function (): void {
    createWorkoutWithStrength($this->user, $this->exercise, $this->now->subDays(100), weight: 70.0);
    createWorkoutWithStrength($this->user, $this->exercise, $this->now->subDays(30), weight: 85.0);

    $result = $this->action->execute($this->user, $this->exercise->id, HistoryRange::ThreeMonths, $this->now);

    expect($result->points)->toHaveCount(1);
    expect($result->points[0]->maxWeight)->toBe(85.0);
});

it('excludes non-completed workouts', function (): void {
    createWorkoutWithStrength($this->user, $this->exercise, $this->now->subDays(3), weight: 85.0, completed: false);
    createWorkoutWithStrength($this->user, $this->exercise, $this->now->subDays(1), weight: 90.0);

    $result = $this->action->execute($this->user, $this->exercise->id, HistoryRange::ThreeMonths, $this->now);

    expect($result->points)->toHaveCount(1);
    expect($result->points[0]->maxWeight)->toBe(90.0);
});

it('only includes target exercise', function (): void {
    $otherExercise = Exercise::factory()->create(['name' => 'Squat']);

    createWorkoutWithStrength($this->user, $this->exercise, $this->now->subDays(3), weight: 85.0);
    createWorkoutWithStrength($this->user, $otherExercise, $this->now->subDays(3), weight: 140.0);

    $result = $this->action->execute($this->user, $this->exercise->id, HistoryRange::ThreeMonths, $this->now);

    expect($result->points)->toHaveCount(1);
    expect($result->points[0]->maxWeight)->toBe(85.0);
});

it('includes all data for AllTime range', function (): void {
    createWorkoutWithStrength($this->user, $this->exercise, $this->now->subDays(400), weight: 60.0);
    createWorkoutWithStrength($this->user, $this->exercise, $this->now->subDays(1), weight: 90.0);

    $result = $this->action->execute($this->user, $this->exercise->id, HistoryRange::AllTime, $this->now);

    expect($result->points)->toHaveCount(2);
});

it('returns empty result when no data', function (): void {
    $result = $this->action->execute($this->user, $this->exercise->id, HistoryRange::ThreeMonths, $this->now);

    expect($result->points)->toBeEmpty();
    expect($result->exerciseName)->toBe('Bench Press');
});
