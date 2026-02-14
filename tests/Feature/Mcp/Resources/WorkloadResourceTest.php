<?php

use App\Enums\BodyPart;
use App\Enums\InjuryType;
use App\Mcp\Resources\WorkloadResource;
use App\Mcp\Servers\WorkoutServer;
use App\Models\Block;
use App\Models\BlockExercise;
use App\Models\Exercise;
use App\Models\Injury;
use App\Models\MuscleGroup;
use App\Models\Section;
use App\Models\StrengthExercise;
use App\Models\User;
use App\Models\Workout;

it('returns workload data for user with completed workouts', function (): void {
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

    $response = WorkoutServer::actingAs($user)->resource(WorkloadResource::class, []);

    $response->assertOk()
        ->assertSee('Workload Summary')
        ->assertSee('Chest');
});

it('shows empty state for user with no workload data', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();

    $response = WorkoutServer::actingAs($user)->resource(WorkloadResource::class, []);

    $response->assertOk()
        ->assertSee('Workload Summary')
        ->assertSee('No workload data available');
});

it('includes zone information', function (): void {
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

    $response = WorkoutServer::actingAs($user)->resource(WorkloadResource::class, []);

    $response->assertOk()
        ->assertSee('Acute Load')
        ->assertSee('Chronic Load')
        ->assertSee('ACWR')
        ->assertSee('Zone');
});

it('includes active injuries', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();
    Injury::factory()->active()->create([
        'user_id' => $user->id,
        'body_part' => BodyPart::Knee,
        'injury_type' => InjuryType::Acute,
    ]);

    $response = WorkoutServer::actingAs($user)->resource(WorkloadResource::class, []);

    $response->assertOk()
        ->assertSee('Active Injuries')
        ->assertSee('knee');
});
