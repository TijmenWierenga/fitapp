<?php

use App\Actions\CalculateWorkload;
use App\Domain\Workload\Enums\Trend;
use App\Enums\BodyPart;
use App\Models\Block;
use App\Models\BlockExercise;
use App\Models\CardioExercise;
use App\Models\Exercise;
use App\Models\Injury;
use App\Models\MuscleGroup;
use App\Models\Section;
use App\Models\StrengthExercise;
use App\Models\User;
use App\Models\Workout;
use Carbon\CarbonImmutable;

beforeEach(function (): void {
    $this->user = User::factory()->withTimezone('UTC')->create();
    $this->action = app(CalculateWorkload::class);
    $this->now = CarbonImmutable::parse('2026-02-14 12:00:00');
});

function createCompletedWorkout(User $user, CarbonImmutable $completedAt, int $rpe = 7): Workout
{
    return Workout::factory()->create([
        'user_id' => $user->id,
        'completed_at' => $completedAt,
        'scheduled_at' => $completedAt->subHour(),
        'rpe' => $rpe,
        'feeling' => 4,
    ]);
}

function addCardioBlock(Workout $workout, int $targetDuration): void
{
    $section = Section::factory()->create(['workout_id' => $workout->id]);
    $block = Block::factory()->distanceDuration()->create(['section_id' => $section->id]);
    $cardio = CardioExercise::factory()->create(['target_duration' => $targetDuration]);
    BlockExercise::factory()->create([
        'block_id' => $block->id,
        'exerciseable_type' => $cardio->getMorphClass(),
        'exerciseable_id' => $cardio->id,
    ]);
}

function createLinkedStrengthExercise(
    Block $block,
    Exercise $exercise,
    int $sets = 3,
    int $reps = 10,
    ?float $weight = null,
    ?float $rpe = 7.0,
): BlockExercise {
    $strength = StrengthExercise::factory()->create([
        'target_sets' => $sets,
        'target_reps_max' => $reps,
        'target_weight' => $weight,
        'target_rpe' => $rpe,
    ]);

    return BlockExercise::factory()->create([
        'block_id' => $block->id,
        'exercise_id' => $exercise->id,
        'exerciseable_type' => $strength->getMorphClass(),
        'exerciseable_id' => $strength->id,
    ]);
}

// --- Session Load ---

it('calculates session load from workouts with estimatable duration', function (): void {
    // 45 min cardio block at RPE 7 → sRPE = 45 * 7 = 315
    $w1 = createCompletedWorkout($this->user, $this->now->subDays(2), rpe: 7);
    addCardioBlock($w1, targetDuration: 2700);

    // 60 min cardio block at RPE 8 → sRPE = 60 * 8 = 480
    $w2 = createCompletedWorkout($this->user, $this->now->subDays(4), rpe: 8);
    addCardioBlock($w2, targetDuration: 3600);

    $result = $this->action->execute($this->user, $this->now);

    expect($result->sessionLoad)->not->toBeNull();
    expect($result->sessionLoad->currentWeeklyTotal)->toBe(315 + 480);
    expect($result->sessionLoad->currentSessionCount)->toBe(2);
});

it('skips workouts without estimatable duration in session load', function (): void {
    // Workout with only strength exercises (no target_duration) → not estimatable
    $workout = createCompletedWorkout($this->user, $this->now->subDays(2), rpe: 7);
    $section = Section::factory()->create(['workout_id' => $workout->id]);
    $block = Block::factory()->create(['section_id' => $section->id]);
    $strength = StrengthExercise::factory()->create();
    BlockExercise::factory()->create([
        'block_id' => $block->id,
        'exerciseable_type' => $strength->getMorphClass(),
        'exerciseable_id' => $strength->id,
    ]);

    $result = $this->action->execute($this->user, $this->now);

    expect($result->sessionLoad)->toBeNull();
});

it('returns null session load when no sessions qualify', function (): void {
    $result = $this->action->execute($this->user, $this->now);

    expect($result->sessionLoad)->toBeNull();
});

// --- Muscle Group Volume ---

it('counts strength exercise sets per muscle group', function (): void {
    $chest = MuscleGroup::factory()->create(['name' => 'chest', 'label' => 'Chest', 'body_part' => BodyPart::Chest]);
    $exercise = Exercise::factory()->create();
    $exercise->muscleGroups()->attach($chest, ['load_factor' => 1.0]);

    $workout = createCompletedWorkout($this->user, $this->now->subDays(2));
    $section = Section::factory()->create(['workout_id' => $workout->id]);
    $block = Block::factory()->create(['section_id' => $section->id]);
    createLinkedStrengthExercise($block, $exercise, sets: 4, reps: 10);

    $result = $this->action->execute($this->user, $this->now);

    $chestVolume = $result->muscleGroupVolume->firstWhere('name', 'chest');
    expect($chestVolume)->not->toBeNull();
    expect($chestVolume->currentWeekSets)->toBe(4.0);
});

it('applies load factors to muscle group volume', function (): void {
    $chest = MuscleGroup::factory()->create(['name' => 'chest', 'label' => 'Chest', 'body_part' => BodyPart::Chest]);
    $triceps = MuscleGroup::factory()->create(['name' => 'triceps', 'label' => 'Triceps', 'body_part' => BodyPart::Triceps]);

    $exercise = Exercise::factory()->create();
    $exercise->muscleGroups()->attach($chest, ['load_factor' => 1.0]);
    $exercise->muscleGroups()->attach($triceps, ['load_factor' => 0.5]);

    $workout = createCompletedWorkout($this->user, $this->now->subDays(2));
    $section = Section::factory()->create(['workout_id' => $workout->id]);
    $block = Block::factory()->create(['section_id' => $section->id]);
    createLinkedStrengthExercise($block, $exercise, sets: 4, reps: 10);

    $result = $this->action->execute($this->user, $this->now);

    $chestVolume = $result->muscleGroupVolume->firstWhere('name', 'chest');
    $tricepsVolume = $result->muscleGroupVolume->firstWhere('name', 'triceps');

    expect($chestVolume->currentWeekSets)->toBe(4.0);
    expect($tricepsVolume->currentWeekSets)->toBe(2.0);
});

it('detects volume trend across weeks', function (): void {
    $chest = MuscleGroup::factory()->create(['name' => 'chest', 'label' => 'Chest', 'body_part' => BodyPart::Chest]);
    $exercise = Exercise::factory()->create();
    $exercise->muscleGroups()->attach($chest, ['load_factor' => 1.0]);

    // Current week: 10 sets (high)
    $w1 = createCompletedWorkout($this->user, $this->now->subDays(2));
    $s1 = Section::factory()->create(['workout_id' => $w1->id]);
    $b1 = Block::factory()->create(['section_id' => $s1->id]);
    createLinkedStrengthExercise($b1, $exercise, sets: 10, reps: 10);

    // Previous weeks: 4 sets each
    foreach ([9, 16, 23] as $daysAgo) {
        $w = createCompletedWorkout($this->user, $this->now->subDays($daysAgo));
        $s = Section::factory()->create(['workout_id' => $w->id]);
        $b = Block::factory()->create(['section_id' => $s->id]);
        createLinkedStrengthExercise($b, $exercise, sets: 4, reps: 10);
    }

    $result = $this->action->execute($this->user, $this->now);

    $chestVolume = $result->muscleGroupVolume->firstWhere('name', 'chest');
    expect($chestVolume->trend)->toBe(Trend::Increasing);
});

// --- Strength Progression ---

it('calculates e1RM progression', function (): void {
    $exercise = Exercise::factory()->create(['name' => 'Bench Press']);
    $chest = MuscleGroup::factory()->create(['name' => 'chest', 'body_part' => BodyPart::Chest]);
    $exercise->muscleGroups()->attach($chest, ['load_factor' => 1.0]);

    // Current period: 100kg x 5 → e1RM = 116.67
    $w1 = createCompletedWorkout($this->user, $this->now->subDays(5));
    $s1 = Section::factory()->create(['workout_id' => $w1->id]);
    $b1 = Block::factory()->create(['section_id' => $s1->id]);
    createLinkedStrengthExercise($b1, $exercise, sets: 3, reps: 5, weight: 100.0);

    // Previous period: 90kg x 5 → e1RM = 105.0
    $w2 = createCompletedWorkout($this->user, $this->now->subDays(35));
    $s2 = Section::factory()->create(['workout_id' => $w2->id]);
    $b2 = Block::factory()->create(['section_id' => $s2->id]);
    createLinkedStrengthExercise($b2, $exercise, sets: 3, reps: 5, weight: 90.0);

    $result = $this->action->execute($this->user, $this->now);

    expect($result->strengthProgression)->not->toBeEmpty();
    $bench = collect($result->strengthProgression)->firstWhere('exerciseName', 'Bench Press');
    expect($bench->currentE1RM)->toEqualWithDelta(116.7, 0.1);
    expect($bench->previousE1RM)->toEqualWithDelta(105.0, 0.1);
    expect($bench->changePct)->toBeGreaterThan(0);
});

it('excludes exercises without weight from strength progression', function (): void {
    $exercise = Exercise::factory()->create(['name' => 'Push Up']);
    $chest = MuscleGroup::factory()->create(['name' => 'chest', 'body_part' => BodyPart::Chest]);
    $exercise->muscleGroups()->attach($chest, ['load_factor' => 1.0]);

    $w = createCompletedWorkout($this->user, $this->now->subDays(2));
    $s = Section::factory()->create(['workout_id' => $w->id]);
    $b = Block::factory()->create(['section_id' => $s->id]);
    createLinkedStrengthExercise($b, $exercise, sets: 3, reps: 10, weight: null);

    $result = $this->action->execute($this->user, $this->now);

    expect($result->strengthProgression)->toBeEmpty();
});

// --- Cross-cutting ---

it('excludes unlinked exercises and counts them', function (): void {
    $workout = createCompletedWorkout($this->user, $this->now->subDays(2));
    $section = Section::factory()->create(['workout_id' => $workout->id]);
    $block = Block::factory()->create(['section_id' => $section->id]);

    $strength = StrengthExercise::factory()->create();
    BlockExercise::factory()->create([
        'block_id' => $block->id,
        'exercise_id' => null,
        'exerciseable_type' => $strength->getMorphClass(),
        'exerciseable_id' => $strength->id,
    ]);

    $result = $this->action->execute($this->user, $this->now);

    expect($result->unlinkedExerciseCount)->toBe(1);
});

it('returns empty results for empty history', function (): void {
    $result = $this->action->execute($this->user, $this->now);

    expect($result->sessionLoad)->toBeNull();
    expect($result->muscleGroupVolume)->toBeEmpty();
    expect($result->strengthProgression)->toBeEmpty();
    expect($result->unlinkedExerciseCount)->toBe(0);
    expect($result->dataSpanDays)->toBe(0);
});

it('includes active injuries in summary', function (): void {
    Injury::factory()->active()->create([
        'user_id' => $this->user->id,
        'body_part' => BodyPart::Knee,
    ]);

    $result = $this->action->execute($this->user, $this->now);

    expect($result->activeInjuries)->toHaveCount(1);
    expect($result->activeInjuries->first()['body_part'])->toBe('knee');
});

it('calculates data span days from earliest workout', function (): void {
    $chest = MuscleGroup::factory()->create(['name' => 'chest', 'body_part' => BodyPart::Chest]);
    $exercise = Exercise::factory()->create();
    $exercise->muscleGroups()->attach($chest, ['load_factor' => 1.0]);

    $workout = createCompletedWorkout($this->user, $this->now->subDays(5));
    $section = Section::factory()->create(['workout_id' => $workout->id]);
    $block = Block::factory()->create(['section_id' => $section->id]);
    createLinkedStrengthExercise($block, $exercise);

    $result = $this->action->execute($this->user, $this->now);

    expect($result->dataSpanDays)->toBe(5);
});

it('only counts completed workouts', function (): void {
    $chest = MuscleGroup::factory()->create(['name' => 'chest', 'body_part' => BodyPart::Chest]);
    $exercise = Exercise::factory()->create();
    $exercise->muscleGroups()->attach($chest, ['load_factor' => 1.0]);

    $workout = Workout::factory()->create([
        'user_id' => $this->user->id,
        'scheduled_at' => $this->now->subDays(2),
        'completed_at' => null,
    ]);
    $section = Section::factory()->create(['workout_id' => $workout->id]);
    $block = Block::factory()->create(['section_id' => $section->id]);
    createLinkedStrengthExercise($block, $exercise);

    $result = $this->action->execute($this->user, $this->now);

    expect($result->muscleGroupVolume)->toBeEmpty();
});
