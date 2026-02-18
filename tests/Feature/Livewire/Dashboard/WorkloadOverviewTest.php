<?php

use App\Enums\BodyPart;
use App\Livewire\Dashboard\MuscleGroupVolume;
use App\Livewire\Dashboard\SessionLoadOverview;
use App\Livewire\Dashboard\StrengthProgression;
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

// --- Session Load Overview ---

it('renders session load empty state when no duration data', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();

    Livewire::actingAs($user)
        ->test(SessionLoadOverview::class)
        ->assertSee('No session load data yet');
});

it('renders session load stats when duration available', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();

    Workout::factory()->create([
        'user_id' => $user->id,
        'completed_at' => now()->subDays(2),
        'scheduled_at' => now()->subDays(2),
        'duration' => 3600,
        'rpe' => 7,
        'feeling' => 4,
    ]);

    Livewire::actingAs($user)
        ->test(SessionLoadOverview::class)
        ->assertSee('Weekly sRPE')
        ->assertSee('Sessions')
        ->assertSee('Monotony')
        ->assertSee('Strain')
        ->assertDontSee('No session load data yet');
});

// --- Muscle Group Volume ---

it('renders volume empty state when no data', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();

    Livewire::actingAs($user)
        ->test(MuscleGroupVolume::class)
        ->assertSee('No volume data yet');
});

it('renders muscle group bars with volume data', function (): void {
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
        ->test(MuscleGroupVolume::class)
        ->assertSee('Chest')
        ->assertSee('sets')
        ->assertDontSee('No volume data yet');
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
        ->test(MuscleGroupVolume::class)
        ->assertSee('1 exercise not linked');
});

it('shows injury badge for injured muscle groups', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();
    $chest = MuscleGroup::factory()->create(['name' => 'chest', 'label' => 'Chest', 'body_part' => BodyPart::Chest]);
    $exercise = Exercise::factory()->create();
    $exercise->muscleGroups()->attach($chest, ['load_factor' => 1.0]);

    Injury::factory()->active()->create([
        'user_id' => $user->id,
        'body_part' => BodyPart::Chest,
    ]);

    $workout = Workout::factory()->create([
        'user_id' => $user->id,
        'completed_at' => now()->subDays(1),
        'scheduled_at' => now()->subDays(1),
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
        ->test(MuscleGroupVolume::class)
        ->assertSee('Injured');
});

// --- Strength Progression ---

it('renders strength progression empty state when no data', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();

    Livewire::actingAs($user)
        ->test(StrengthProgression::class)
        ->assertSee('No strength progression data yet');
});

it('renders strength progression table when data available', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();
    $exercise = Exercise::factory()->create(['name' => 'Bench Press']);
    $chest = MuscleGroup::factory()->create(['name' => 'chest', 'body_part' => BodyPart::Chest]);
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
    $strength = StrengthExercise::factory()->create([
        'target_sets' => 3,
        'target_reps_max' => 5,
        'target_weight' => 100.0,
        'target_rpe' => 7.0,
    ]);
    BlockExercise::factory()->create([
        'block_id' => $block->id,
        'exercise_id' => $exercise->id,
        'exerciseable_type' => $strength->getMorphClass(),
        'exerciseable_id' => $strength->id,
    ]);

    Livewire::actingAs($user)
        ->test(StrengthProgression::class)
        ->assertSee('Bench Press')
        ->assertSee('kg')
        ->assertDontSee('No strength progression data yet');
});
