<?php

use App\Actions\CalculateWorkload;
use App\Enums\BodyPart;
use App\Models\Block;
use App\Models\BlockExercise;
use App\Models\CardioExercise;
use App\Models\DurationExercise;
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

function createCompletedWorkout(User $user, CarbonImmutable $completedAt): Workout
{
    return Workout::factory()->create([
        'user_id' => $user->id,
        'completed_at' => $completedAt,
        'scheduled_at' => $completedAt->subHour(),
        'rpe' => 7,
        'feeling' => 4,
    ]);
}

function createLinkedStrengthExercise(
    Block $block,
    Exercise $exercise,
    int $sets = 3,
    int $reps = 10,
    float $rpe = 7.0,
): BlockExercise {
    $strength = StrengthExercise::factory()->create([
        'target_sets' => $sets,
        'target_reps_max' => $reps,
        'target_rpe' => $rpe,
    ]);

    return BlockExercise::factory()->create([
        'block_id' => $block->id,
        'exercise_id' => $exercise->id,
        'exerciseable_type' => $strength->getMorphClass(),
        'exerciseable_id' => $strength->id,
    ]);
}

it('calculates strength exercise load correctly', function (): void {
    $chest = MuscleGroup::factory()->create(['name' => 'chest', 'body_part' => BodyPart::Chest]);
    $exercise = Exercise::factory()->create();
    $exercise->muscleGroups()->attach($chest, ['load_factor' => 1.0]);

    $workout = createCompletedWorkout($this->user, $this->now->subDays(2));
    $section = Section::factory()->create(['workout_id' => $workout->id]);
    $block = Block::factory()->create(['section_id' => $section->id]);

    // 3 sets x 10 reps x (7/10) RPE x 1.0 load_factor = 21.0
    createLinkedStrengthExercise($block, $exercise, sets: 3, reps: 10, rpe: 7.0);

    $result = $this->action->execute($this->user, $this->now);

    $chestWorkload = $result->muscleGroups->firstWhere('muscleGroupName', 'chest');
    expect($chestWorkload)->not->toBeNull();
    expect($chestWorkload->acuteLoad)->toBe(21.0);
});

it('applies primary and secondary load factors', function (): void {
    $chest = MuscleGroup::factory()->create(['name' => 'chest', 'body_part' => BodyPart::Chest]);
    $triceps = MuscleGroup::factory()->create(['name' => 'triceps', 'body_part' => BodyPart::Triceps]);

    $exercise = Exercise::factory()->create();
    $exercise->muscleGroups()->attach($chest, ['load_factor' => 1.0]);
    $exercise->muscleGroups()->attach($triceps, ['load_factor' => 0.5]);

    $workout = createCompletedWorkout($this->user, $this->now->subDays(2));
    $section = Section::factory()->create(['workout_id' => $workout->id]);
    $block = Block::factory()->create(['section_id' => $section->id]);

    // volume = 3 x 10 x (7/10) = 21.0
    createLinkedStrengthExercise($block, $exercise, sets: 3, reps: 10, rpe: 7.0);

    $result = $this->action->execute($this->user, $this->now);

    $chestWorkload = $result->muscleGroups->firstWhere('muscleGroupName', 'chest');
    $tricepsWorkload = $result->muscleGroups->firstWhere('muscleGroupName', 'triceps');

    expect($chestWorkload->acuteLoad)->toBe(21.0);    // 21.0 * 1.0
    expect($tricepsWorkload->acuteLoad)->toBe(10.5);   // 21.0 * 0.5
});

it('only counts completed workouts', function (): void {
    $chest = MuscleGroup::factory()->create(['name' => 'chest', 'body_part' => BodyPart::Chest]);
    $exercise = Exercise::factory()->create();
    $exercise->muscleGroups()->attach($chest, ['load_factor' => 1.0]);

    // Uncompleted workout - should be excluded
    $workout = Workout::factory()->create([
        'user_id' => $this->user->id,
        'scheduled_at' => $this->now->subDays(2),
        'completed_at' => null,
    ]);
    $section = Section::factory()->create(['workout_id' => $workout->id]);
    $block = Block::factory()->create(['section_id' => $section->id]);
    createLinkedStrengthExercise($block, $exercise);

    $result = $this->action->execute($this->user, $this->now);

    expect($result->muscleGroups)->toBeEmpty();
});

it('separates acute and chronic windows', function (): void {
    $chest = MuscleGroup::factory()->create(['name' => 'chest', 'body_part' => BodyPart::Chest]);
    $exercise = Exercise::factory()->create();
    $exercise->muscleGroups()->attach($chest, ['load_factor' => 1.0]);

    // Acute window workout (within 7 days)
    $acuteWorkout = createCompletedWorkout($this->user, $this->now->subDays(3));
    $section1 = Section::factory()->create(['workout_id' => $acuteWorkout->id]);
    $block1 = Block::factory()->create(['section_id' => $section1->id]);
    createLinkedStrengthExercise($block1, $exercise, sets: 3, reps: 10, rpe: 10.0);
    // volume = 3 * 10 * 1.0 = 30

    // Chronic-only workout (older than 7 days but within 28)
    $chronicWorkout = createCompletedWorkout($this->user, $this->now->subDays(14));
    $section2 = Section::factory()->create(['workout_id' => $chronicWorkout->id]);
    $block2 = Block::factory()->create(['section_id' => $section2->id]);
    createLinkedStrengthExercise($block2, $exercise, sets: 2, reps: 10, rpe: 10.0);
    // volume = 2 * 10 * 1.0 = 20

    $result = $this->action->execute($this->user, $this->now);

    $chestWorkload = $result->muscleGroups->firstWhere('muscleGroupName', 'chest');
    expect($chestWorkload->acuteLoad)->toBe(30.0);
    // chronic = (30 + 20) / 4 weeks = 12.5
    expect($chestWorkload->chronicLoad)->toBe(12.5);
});

it('calculates ACWR and determines zones', function (): void {
    $chest = MuscleGroup::factory()->create(['name' => 'chest', 'body_part' => BodyPart::Chest]);
    $exercise = Exercise::factory()->create();
    $exercise->muscleGroups()->attach($chest, ['load_factor' => 1.0]);

    // Create workouts across 4 weeks (even distribution)
    foreach ([3, 10, 17, 24] as $daysAgo) {
        $workout = createCompletedWorkout($this->user, $this->now->subDays($daysAgo));
        $section = Section::factory()->create(['workout_id' => $workout->id]);
        $block = Block::factory()->create(['section_id' => $section->id]);
        createLinkedStrengthExercise($block, $exercise, sets: 3, reps: 10, rpe: 10.0);
        // volume per workout = 3 * 10 * 1.0 = 30
    }

    $result = $this->action->execute($this->user, $this->now);

    $chestWorkload = $result->muscleGroups->firstWhere('muscleGroupName', 'chest');
    // acute = 30 (one workout in last 7 days)
    // chronic = 120 / 4 weeks = 30
    // ACWR = 30 / 30 = 1.0
    expect($chestWorkload->acwr)->toBe(1.0);
    expect($chestWorkload->zone)->toBe('sweet_spot');
    expect($chestWorkload->zoneColor)->toBe('green');
});

it('excludes unlinked exercises and counts them', function (): void {
    $chest = MuscleGroup::factory()->create(['name' => 'chest', 'body_part' => BodyPart::Chest]);

    $workout = createCompletedWorkout($this->user, $this->now->subDays(2));
    $section = Section::factory()->create(['workout_id' => $workout->id]);
    $block = Block::factory()->create(['section_id' => $section->id]);

    // Unlinked exercise (no exercise_id)
    $strength = StrengthExercise::factory()->create();
    BlockExercise::factory()->create([
        'block_id' => $block->id,
        'exercise_id' => null,
        'exerciseable_type' => $strength->getMorphClass(),
        'exerciseable_id' => $strength->id,
    ]);

    $result = $this->action->execute($this->user, $this->now);

    expect($result->muscleGroups)->toBeEmpty();
    expect($result->unlinkedExerciseCount)->toBe(1);
});

it('returns empty results for empty history', function (): void {
    $result = $this->action->execute($this->user, $this->now);

    expect($result->muscleGroups)->toBeEmpty();
    expect($result->unlinkedExerciseCount)->toBe(0);
});

it('uses block rounds as fallback when target_sets is null', function (): void {
    $chest = MuscleGroup::factory()->create(['name' => 'chest', 'body_part' => BodyPart::Chest]);
    $exercise = Exercise::factory()->create();
    $exercise->muscleGroups()->attach($chest, ['load_factor' => 1.0]);

    $workout = createCompletedWorkout($this->user, $this->now->subDays(2));
    $section = Section::factory()->create(['workout_id' => $workout->id]);
    $block = Block::factory()->circuit()->create([
        'section_id' => $section->id,
        'rounds' => 4,
    ]);

    $strength = StrengthExercise::factory()->create([
        'target_sets' => null,
        'target_reps_max' => 10,
        'target_rpe' => 8.0,
    ]);

    BlockExercise::factory()->create([
        'block_id' => $block->id,
        'exercise_id' => $exercise->id,
        'exerciseable_type' => $strength->getMorphClass(),
        'exerciseable_id' => $strength->id,
    ]);

    $result = $this->action->execute($this->user, $this->now);

    $chestWorkload = $result->muscleGroups->firstWhere('muscleGroupName', 'chest');
    // 4 rounds x 10 reps x (8/10) RPE = 32.0
    expect($chestWorkload->acuteLoad)->toBe(32.0);
});

it('calculates cardio exercise load', function (): void {
    $quads = MuscleGroup::factory()->create(['name' => 'quadriceps', 'body_part' => BodyPart::Quadriceps]);
    $exercise = Exercise::factory()->create();
    $exercise->muscleGroups()->attach($quads, ['load_factor' => 1.0]);

    $workout = createCompletedWorkout($this->user, $this->now->subDays(2));
    $section = Section::factory()->create(['workout_id' => $workout->id]);
    $block = Block::factory()->create(['section_id' => $section->id]);

    $cardio = CardioExercise::factory()->create([
        'target_duration' => 1800, // 30 minutes
        'target_heart_rate_zone' => 4,
    ]);

    BlockExercise::factory()->create([
        'block_id' => $block->id,
        'exercise_id' => $exercise->id,
        'exerciseable_type' => $cardio->getMorphClass(),
        'exerciseable_id' => $cardio->id,
    ]);

    $result = $this->action->execute($this->user, $this->now);

    $quadsWorkload = $result->muscleGroups->firstWhere('muscleGroupName', 'quadriceps');
    // (30min / 10) * (4 / 5) * 1.0 = 2.4
    expect($quadsWorkload->acuteLoad)->toEqualWithDelta(2.4, 0.001);
});

it('calculates duration exercise load', function (): void {
    $core = MuscleGroup::factory()->create(['name' => 'abdominals', 'body_part' => BodyPart::Core]);
    $exercise = Exercise::factory()->create();
    $exercise->muscleGroups()->attach($core, ['load_factor' => 1.0]);

    $workout = createCompletedWorkout($this->user, $this->now->subDays(2));
    $section = Section::factory()->create(['workout_id' => $workout->id]);
    $block = Block::factory()->create(['section_id' => $section->id]);

    $duration = DurationExercise::factory()->create([
        'target_duration' => 300, // 5 minutes
        'target_rpe' => 6.0,
    ]);

    BlockExercise::factory()->create([
        'block_id' => $block->id,
        'exercise_id' => $exercise->id,
        'exerciseable_type' => $duration->getMorphClass(),
        'exerciseable_id' => $duration->id,
    ]);

    $result = $this->action->execute($this->user, $this->now);

    $coreWorkload = $result->muscleGroups->firstWhere('muscleGroupName', 'abdominals');
    // 5min * (6/10) * 1.0 = 3.0
    expect($coreWorkload->acuteLoad)->toBe(3.0);
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

it('uses default RPE when not specified for strength', function (): void {
    $chest = MuscleGroup::factory()->create(['name' => 'chest', 'body_part' => BodyPart::Chest]);
    $exercise = Exercise::factory()->create();
    $exercise->muscleGroups()->attach($chest, ['load_factor' => 1.0]);

    $workout = createCompletedWorkout($this->user, $this->now->subDays(2));
    $section = Section::factory()->create(['workout_id' => $workout->id]);
    $block = Block::factory()->create(['section_id' => $section->id]);

    $strength = StrengthExercise::factory()->create([
        'target_sets' => 3,
        'target_reps_max' => 10,
        'target_rpe' => null,
    ]);

    BlockExercise::factory()->create([
        'block_id' => $block->id,
        'exercise_id' => $exercise->id,
        'exerciseable_type' => $strength->getMorphClass(),
        'exerciseable_id' => $strength->id,
    ]);

    $result = $this->action->execute($this->user, $this->now);

    $chestWorkload = $result->muscleGroups->firstWhere('muscleGroupName', 'chest');
    // 3 x 10 x (5/10) = 15.0 (default RPE = 5)
    expect($chestWorkload->acuteLoad)->toBe(15.0);
});
