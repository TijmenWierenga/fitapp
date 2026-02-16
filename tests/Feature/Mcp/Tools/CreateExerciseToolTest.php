<?php

use App\Enums\BodyPart;
use App\Enums\Fit\GarminExerciseCategory;
use App\Mcp\Servers\WorkoutServer;
use App\Mcp\Tools\CreateExerciseTool;
use App\Models\Exercise;
use App\Models\MuscleGroup;
use App\Models\User;

it('allows admin to create exercise with all fields and muscle groups', function (): void {
    $admin = User::factory()->admin()->withTimezone('UTC')->create();

    $chest = MuscleGroup::factory()->create(['name' => 'chest', 'label' => 'Chest', 'body_part' => BodyPart::Chest]);
    $triceps = MuscleGroup::factory()->create(['name' => 'triceps', 'label' => 'Triceps', 'body_part' => BodyPart::Triceps]);

    $response = WorkoutServer::actingAs($admin)->tool(CreateExerciseTool::class, [
        'name' => 'Incline Dumbbell Press',
        'category' => 'strength',
        'level' => 'intermediate',
        'force' => 'push',
        'mechanic' => 'compound',
        'equipment' => 'dumbbell',
        'description' => 'A chest pressing movement on an incline bench.',
        'instructions' => ['Lie on an incline bench.', 'Press the dumbbells up.'],
        'aliases' => ['Incline DB Press'],
        'tips' => ['Keep your shoulder blades retracted.'],
        'primary_muscles' => ['chest'],
        'secondary_muscles' => ['triceps'],
    ]);

    $response->assertOk()
        ->assertSee('Incline Dumbbell Press')
        ->assertSee('incline-dumbbell-press')
        ->assertSee('"category": "strength"')
        ->assertSee('"level": "intermediate"')
        ->assertSee('"force": "push"')
        ->assertSee('"mechanic": "compound"')
        ->assertSee('"equipment": "dumbbell"')
        ->assertSee('A chest pressing movement')
        ->assertSee('Lie on an incline bench.')
        ->assertSee('Incline DB Press')
        ->assertSee('Keep your shoulder blades retracted.');

    $exercise = Exercise::where('name', 'Incline Dumbbell Press')->first();
    expect($exercise)->not->toBeNull();
    expect($exercise->primaryMuscles)->toHaveCount(1);
    expect($exercise->secondaryMuscles)->toHaveCount(1);
});

it('hides tool from non-admin users', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();

    $response = WorkoutServer::actingAs($user)->tool(CreateExerciseTool::class, [
        'name' => 'Forbidden Exercise',
        'category' => 'strength',
        'level' => 'beginner',
    ]);

    $response->assertHasErrors();

    expect(Exercise::where('name', 'Forbidden Exercise')->exists())->toBeFalse();
});

it('creates exercise with only required fields', function (): void {
    $admin = User::factory()->admin()->withTimezone('UTC')->create();

    $response = WorkoutServer::actingAs($admin)->tool(CreateExerciseTool::class, [
        'name' => 'Basic Push Up',
        'category' => 'strength',
        'level' => 'beginner',
    ]);

    $response->assertOk()
        ->assertSee('Basic Push Up')
        ->assertSee('basic-push-up')
        ->assertSee('"garmin_compatible": false');

    $exercise = Exercise::where('name', 'Basic Push Up')->first();
    expect($exercise)->not->toBeNull();
    expect($exercise->force)->toBeNull();
    expect($exercise->equipment)->toBeNull();
    expect($exercise->garmin_exercise_category)->toBeNull();
    expect($exercise->garmin_exercise_name)->toBeNull();
});

it('rejects duplicate exercise name', function (): void {
    $admin = User::factory()->admin()->withTimezone('UTC')->create();

    Exercise::factory()->create(['name' => 'Bench Press']);

    $response = WorkoutServer::actingAs($admin)->tool(CreateExerciseTool::class, [
        'name' => 'Bench Press',
        'category' => 'strength',
        'level' => 'intermediate',
    ]);

    $response->assertHasErrors()
        ->assertSee('name');
});

it('rejects invalid muscle group names', function (): void {
    $admin = User::factory()->admin()->withTimezone('UTC')->create();

    $response = WorkoutServer::actingAs($admin)->tool(CreateExerciseTool::class, [
        'name' => 'Test Exercise',
        'category' => 'strength',
        'level' => 'beginner',
        'primary_muscles' => ['nonexistent_muscle'],
    ]);

    $response->assertHasErrors()
        ->assertSee('primary_muscles');
});

it('rejects muscle group appearing in both primary and secondary', function (): void {
    $admin = User::factory()->admin()->withTimezone('UTC')->create();

    MuscleGroup::factory()->create(['name' => 'chest', 'label' => 'Chest', 'body_part' => BodyPart::Chest]);

    $response = WorkoutServer::actingAs($admin)->tool(CreateExerciseTool::class, [
        'name' => 'Overlap Exercise',
        'category' => 'strength',
        'level' => 'beginner',
        'primary_muscles' => ['chest'],
        'secondary_muscles' => ['chest'],
    ]);

    $response->assertHasErrors()
        ->assertSee('cannot appear in both');

    expect(Exercise::where('name', 'Overlap Exercise')->exists())->toBeFalse();
});

it('auto-generates slug from name', function (): void {
    $admin = User::factory()->admin()->withTimezone('UTC')->create();

    $response = WorkoutServer::actingAs($admin)->tool(CreateExerciseTool::class, [
        'name' => 'Standing Barbell Curl',
        'category' => 'strength',
        'level' => 'beginner',
    ]);

    $response->assertOk();

    $exercise = Exercise::where('name', 'Standing Barbell Curl')->first();
    expect($exercise->slug)->toBe('standing-barbell-curl');
});

it('returns muscle groups with correct load factors', function (): void {
    $admin = User::factory()->admin()->withTimezone('UTC')->create();

    MuscleGroup::factory()->create(['name' => 'chest', 'label' => 'Chest', 'body_part' => BodyPart::Chest]);
    MuscleGroup::factory()->create(['name' => 'triceps', 'label' => 'Triceps', 'body_part' => BodyPart::Triceps]);
    MuscleGroup::factory()->create(['name' => 'shoulders', 'label' => 'Shoulders', 'body_part' => BodyPart::Shoulder]);

    $response = WorkoutServer::actingAs($admin)->tool(CreateExerciseTool::class, [
        'name' => 'Flat Bench Press',
        'category' => 'strength',
        'level' => 'intermediate',
        'primary_muscles' => ['chest'],
        'secondary_muscles' => ['triceps', 'shoulders'],
    ]);

    $response->assertOk()
        ->assertSee('primary_muscles')
        ->assertSee('"name": "chest"')
        ->assertSee('secondary_muscles')
        ->assertSee('"name": "triceps"')
        ->assertSee('"name": "shoulders"');

    $exercise = Exercise::where('name', 'Flat Bench Press')->first();
    expect($exercise->primaryMuscles)->toHaveCount(1);
    expect((float) $exercise->primaryMuscles->first()->pivot->load_factor)->toBe(1.0);
    expect($exercise->secondaryMuscles)->toHaveCount(2);
    expect((float) $exercise->secondaryMuscles->first()->pivot->load_factor)->toBe(0.5);
});

it('creates exercise with Garmin mapping', function (): void {
    $admin = User::factory()->admin()->withTimezone('UTC')->create();

    $response = WorkoutServer::actingAs($admin)->tool(CreateExerciseTool::class, [
        'name' => 'Barbell Bench Press',
        'category' => 'strength',
        'level' => 'intermediate',
        'garmin_exercise_category' => GarminExerciseCategory::BenchPress->value,
        'garmin_exercise_name' => 0,
    ]);

    $response->assertOk()
        ->assertSee('Barbell Bench Press')
        ->assertSee('"garmin_compatible": true');

    $exercise = Exercise::where('name', 'Barbell Bench Press')->first();
    expect($exercise->garmin_exercise_category)->toBe(GarminExerciseCategory::BenchPress);
    expect($exercise->garmin_exercise_name)->toBe(0);
});

it('rejects invalid Garmin exercise category', function (): void {
    $admin = User::factory()->admin()->withTimezone('UTC')->create();

    $response = WorkoutServer::actingAs($admin)->tool(CreateExerciseTool::class, [
        'name' => 'Invalid Garmin Exercise',
        'category' => 'strength',
        'level' => 'beginner',
        'garmin_exercise_category' => 999,
        'garmin_exercise_name' => 0,
    ]);

    $response->assertHasErrors()
        ->assertSee('garmin exercise category');

    expect(Exercise::where('name', 'Invalid Garmin Exercise')->exists())->toBeFalse();
});

it('requires both Garmin fields or neither', function (): void {
    $admin = User::factory()->admin()->withTimezone('UTC')->create();

    $categoryOnly = WorkoutServer::actingAs($admin)->tool(CreateExerciseTool::class, [
        'name' => 'Category Only Exercise',
        'category' => 'strength',
        'level' => 'beginner',
        'garmin_exercise_category' => GarminExerciseCategory::Squat->value,
    ]);

    $categoryOnly->assertHasErrors()
        ->assertSee('garmin_exercise_category and garmin_exercise_name must be provided together');

    expect(Exercise::where('name', 'Category Only Exercise')->exists())->toBeFalse();

    $nameOnly = WorkoutServer::actingAs($admin)->tool(CreateExerciseTool::class, [
        'name' => 'Name Only Exercise',
        'category' => 'strength',
        'level' => 'beginner',
        'garmin_exercise_name' => 5,
    ]);

    $nameOnly->assertHasErrors()
        ->assertSee('garmin_exercise_category and garmin_exercise_name must be provided together');

    expect(Exercise::where('name', 'Name Only Exercise')->exists())->toBeFalse();
});
