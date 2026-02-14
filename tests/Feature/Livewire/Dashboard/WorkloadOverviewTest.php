<?php

use App\Enums\BodyPart;
use App\Livewire\Dashboard\WorkloadOverview;
use App\Models\Block;
use App\Models\BlockExercise;
use App\Models\Exercise;
use App\Models\Injury;
use App\Models\MuscleGroup;
use App\Models\Section;
use App\Models\StrengthExercise;
use App\Models\User;
use App\Models\Workout;
use Livewire\Livewire;

it('renders empty state when no workload data', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();

    Livewire::actingAs($user)
        ->test(WorkloadOverview::class)
        ->assertSee('No workload data yet');
});

it('renders muscle group bars with load data', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();
    $chest = MuscleGroup::factory()->create(['name' => 'chest', 'label' => 'Chest', 'body_part' => BodyPart::Chest]);
    $exercise = Exercise::factory()->create();
    $exercise->muscleGroups()->attach($chest, ['load_factor' => 1.0]);

    $workout = Workout::factory()->create([
        'user_id' => $user->id,
        'completed_at' => now()->subDays(2),
        'scheduled_at' => now()->subDays(2),
        'rpe' => 7,
        'feeling' => 4,
    ]);
    $section = Section::factory()->create(['workout_id' => $workout->id]);
    $block = Block::factory()->create(['section_id' => $section->id]);
    $strength = StrengthExercise::factory()->create(['target_sets' => 3, 'target_reps_max' => 10, 'target_rpe' => 7.0]);
    BlockExercise::factory()->create([
        'block_id' => $block->id,
        'exercise_id' => $exercise->id,
        'exerciseable_type' => $strength->getMorphClass(),
        'exerciseable_id' => $strength->id,
    ]);

    Livewire::actingAs($user)
        ->test(WorkloadOverview::class)
        ->assertSee('Chest')
        ->assertDontSee('No workload data yet');
});

it('shows unlinked exercise count', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();

    $workout = Workout::factory()->create([
        'user_id' => $user->id,
        'completed_at' => now()->subDays(2),
        'scheduled_at' => now()->subDays(2),
        'rpe' => 7,
        'feeling' => 4,
    ]);
    $section = Section::factory()->create(['workout_id' => $workout->id]);
    $block = Block::factory()->create(['section_id' => $section->id]);
    $strength = StrengthExercise::factory()->create();
    BlockExercise::factory()->create([
        'block_id' => $block->id,
        'exercise_id' => null,
        'exerciseable_type' => $strength->getMorphClass(),
        'exerciseable_id' => $strength->id,
    ]);

    Livewire::actingAs($user)
        ->test(WorkloadOverview::class)
        ->assertSee('1 exercise not linked');
});

it('renders explanation text and guide link', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();

    Livewire::actingAs($user)
        ->test(WorkloadOverview::class)
        ->assertSee('Your recent training load per muscle group.')
        ->assertSee('Learn how it works');
});

it('renders tooltip content for zone legend', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();
    $chest = MuscleGroup::factory()->create(['name' => 'chest', 'label' => 'Chest', 'body_part' => BodyPart::Chest]);
    $exercise = Exercise::factory()->create();
    $exercise->muscleGroups()->attach($chest, ['load_factor' => 1.0]);

    $workout = Workout::factory()->create([
        'user_id' => $user->id,
        'completed_at' => now()->subDays(2),
        'scheduled_at' => now()->subDays(2),
        'rpe' => 7,
        'feeling' => 4,
    ]);
    $section = Section::factory()->create(['workout_id' => $workout->id]);
    $block = Block::factory()->create(['section_id' => $section->id]);
    $strength = StrengthExercise::factory()->create(['target_sets' => 3, 'target_reps_max' => 10, 'target_rpe' => 7.0]);
    BlockExercise::factory()->create([
        'block_id' => $block->id,
        'exercise_id' => $exercise->id,
        'exerciseable_type' => $strength->getMorphClass(),
        'exerciseable_id' => $strength->id,
    ]);

    Livewire::actingAs($user)
        ->test(WorkloadOverview::class)
        ->assertSee('ACWR 0.8–1.3 — optimal training zone.')
        ->assertSee('Total volume for this muscle group in the last 7 days.');
});

it('shows data reliability warning when data span is less than 28 days', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();
    $chest = MuscleGroup::factory()->create(['name' => 'chest', 'label' => 'Chest', 'body_part' => BodyPart::Chest]);
    $exercise = Exercise::factory()->create();
    $exercise->muscleGroups()->attach($chest, ['load_factor' => 1.0]);

    $workout = Workout::factory()->create([
        'user_id' => $user->id,
        'completed_at' => now()->subDays(5),
        'scheduled_at' => now()->subDays(5),
        'rpe' => 7,
        'feeling' => 4,
    ]);
    $section = Section::factory()->create(['workout_id' => $workout->id]);
    $block = Block::factory()->create(['section_id' => $section->id]);
    $strength = StrengthExercise::factory()->create(['target_sets' => 3, 'target_reps_max' => 10, 'target_rpe' => 7.0]);
    BlockExercise::factory()->create([
        'block_id' => $block->id,
        'exercise_id' => $exercise->id,
        'exerciseable_type' => $strength->getMorphClass(),
        'exerciseable_id' => $strength->id,
    ]);

    Livewire::actingAs($user)
        ->test(WorkloadOverview::class)
        ->assertSee('ACWR zones require 4 weeks of history to be reliable');
});

it('does not show data reliability warning on empty state', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();

    Livewire::actingAs($user)
        ->test(WorkloadOverview::class)
        ->assertDontSee('ACWR zones require 4 weeks of history to be reliable');
});

it('shows injury warning for injured muscles in caution zone', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();
    $chest = MuscleGroup::factory()->create(['name' => 'chest', 'label' => 'Chest', 'body_part' => BodyPart::Chest]);
    $exercise = Exercise::factory()->create();
    $exercise->muscleGroups()->attach($chest, ['load_factor' => 1.0]);

    Injury::factory()->active()->create([
        'user_id' => $user->id,
        'body_part' => BodyPart::Chest,
    ]);

    // Create a spike in acute load to trigger caution/danger zone
    // Acute = high, chronic = low → high ACWR
    $recentWorkout = Workout::factory()->create([
        'user_id' => $user->id,
        'completed_at' => now()->subDays(1),
        'scheduled_at' => now()->subDays(1),
        'rpe' => 9,
        'feeling' => 3,
    ]);
    $section = Section::factory()->create(['workout_id' => $recentWorkout->id]);
    $block = Block::factory()->create(['section_id' => $section->id]);

    // Large volume exercise
    $strength = StrengthExercise::factory()->create([
        'target_sets' => 10,
        'target_reps_max' => 15,
        'target_rpe' => 10.0,
    ]);
    BlockExercise::factory()->create([
        'block_id' => $block->id,
        'exercise_id' => $exercise->id,
        'exerciseable_type' => $strength->getMorphClass(),
        'exerciseable_id' => $strength->id,
    ]);

    // No chronic history → ACWR will be 0 (no chronic load), zone is inactive
    // To trigger caution, we need chronic load too. Add an old workout with low load
    $oldWorkout = Workout::factory()->create([
        'user_id' => $user->id,
        'completed_at' => now()->subDays(20),
        'scheduled_at' => now()->subDays(20),
        'rpe' => 5,
        'feeling' => 4,
    ]);
    $section2 = Section::factory()->create(['workout_id' => $oldWorkout->id]);
    $block2 = Block::factory()->create(['section_id' => $section2->id]);
    $strength2 = StrengthExercise::factory()->create([
        'target_sets' => 1,
        'target_reps_max' => 5,
        'target_rpe' => 5.0,
    ]);
    BlockExercise::factory()->create([
        'block_id' => $block2->id,
        'exercise_id' => $exercise->id,
        'exerciseable_type' => $strength2->getMorphClass(),
        'exerciseable_id' => $strength2->id,
    ]);

    Livewire::actingAs($user)
        ->test(WorkloadOverview::class)
        ->assertSee('Injured');
});
