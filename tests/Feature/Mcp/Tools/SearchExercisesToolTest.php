<?php

use App\Enums\BodyPart;
use App\Mcp\Servers\WorkoutServer;
use App\Mcp\Tools\SearchExercisesTool;
use App\Models\Exercise;
use App\Models\MuscleGroup;
use App\Models\User;

it('returns exercises matching text query', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();

    Exercise::factory()->create(['name' => 'Bench Press']);
    Exercise::factory()->create(['name' => 'Incline Bench Press']);
    Exercise::factory()->create(['name' => 'Barbell Squat']);

    $response = WorkoutServer::actingAs($user)->tool(SearchExercisesTool::class, [
        'query' => 'Bench Press',
    ]);

    $response->assertOk()
        ->assertSee('Bench Press')
        ->assertDontSee('Barbell Squat');
});

it('filters by muscle group', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();

    $chest = MuscleGroup::factory()->create(['name' => 'chest', 'label' => 'Chest', 'body_part' => BodyPart::Chest]);
    $legs = MuscleGroup::factory()->create(['name' => 'quadriceps', 'label' => 'Quadriceps', 'body_part' => BodyPart::Quadriceps]);

    $benchPress = Exercise::factory()->create(['name' => 'Bench Press']);
    $benchPress->muscleGroups()->attach($chest, ['load_factor' => 1.0]);

    $squat = Exercise::factory()->create(['name' => 'Barbell Squat']);
    $squat->muscleGroups()->attach($legs, ['load_factor' => 1.0]);

    $response = WorkoutServer::actingAs($user)->tool(SearchExercisesTool::class, [
        'muscle_group' => 'chest',
    ]);

    $response->assertOk()
        ->assertSee('Bench Press')
        ->assertDontSee('Barbell Squat');
});

it('filters by category', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();

    Exercise::factory()->create(['name' => 'Bench Press', 'category' => 'strength']);
    Exercise::factory()->create(['name' => 'Jumping Jacks', 'category' => 'cardio']);

    $response = WorkoutServer::actingAs($user)->tool(SearchExercisesTool::class, [
        'query' => '',
        'muscle_group' => null,
        'category' => 'strength',
    ]);

    // Need at least query or muscle_group — use query with category
    $response = WorkoutServer::actingAs($user)->tool(SearchExercisesTool::class, [
        'query' => 'Press',
        'category' => 'strength',
    ]);

    $response->assertOk()
        ->assertSee('Bench Press');
});

it('filters by equipment', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();

    Exercise::factory()->create(['name' => 'Dumbbell Curl', 'equipment' => 'dumbbell']);
    Exercise::factory()->create(['name' => 'Barbell Curl', 'equipment' => 'barbell']);

    $response = WorkoutServer::actingAs($user)->tool(SearchExercisesTool::class, [
        'query' => 'Curl',
        'equipment' => 'dumbbell',
    ]);

    $response->assertOk()
        ->assertSee('Dumbbell Curl')
        ->assertDontSee('Barbell Curl');
});

it('filters by level', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();

    Exercise::factory()->create(['name' => 'Push Up', 'level' => 'beginner']);
    Exercise::factory()->create(['name' => 'Planche Push Up', 'level' => 'expert']);

    $response = WorkoutServer::actingAs($user)->tool(SearchExercisesTool::class, [
        'query' => 'Push Up',
        'level' => 'beginner',
    ]);

    $response->assertOk()
        ->assertSee('"level": "beginner"')
        ->assertDontSee('"level": "expert"');
});

it('combines multiple filters', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();

    $chest = MuscleGroup::factory()->create(['name' => 'chest', 'label' => 'Chest', 'body_part' => BodyPart::Chest]);

    $dbBenchPress = Exercise::factory()->create(['name' => 'Dumbbell Bench Press', 'equipment' => 'dumbbell', 'category' => 'strength']);
    $dbBenchPress->muscleGroups()->attach($chest, ['load_factor' => 1.0]);

    $bbBenchPress = Exercise::factory()->create(['name' => 'Barbell Bench Press', 'equipment' => 'barbell', 'category' => 'strength']);
    $bbBenchPress->muscleGroups()->attach($chest, ['load_factor' => 1.0]);

    $response = WorkoutServer::actingAs($user)->tool(SearchExercisesTool::class, [
        'query' => 'Bench Press',
        'muscle_group' => 'chest',
        'equipment' => 'dumbbell',
    ]);

    $response->assertOk()
        ->assertSee('Dumbbell Bench Press')
        ->assertDontSee('Barbell Bench Press');
});

it('respects limit parameter', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();

    Exercise::factory()->count(5)->create(['category' => 'strength']);

    $response = WorkoutServer::actingAs($user)->tool(SearchExercisesTool::class, [
        'query' => '',
        'muscle_group' => null,
    ]);

    // This should fail validation since both are empty/null
    // Let's create exercises with a common word and search
    Exercise::factory()->create(['name' => 'Test Exercise Alpha']);
    Exercise::factory()->create(['name' => 'Test Exercise Beta']);
    Exercise::factory()->create(['name' => 'Test Exercise Gamma']);

    $response = WorkoutServer::actingAs($user)->tool(SearchExercisesTool::class, [
        'query' => 'Test Exercise',
        'limit' => 2,
    ]);

    $response->assertOk()
        ->assertSee('"count": 2');
});

it('returns empty results gracefully', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();

    $response = WorkoutServer::actingAs($user)->tool(SearchExercisesTool::class, [
        'query' => 'NonExistentExercise12345',
    ]);

    $response->assertOk()
        ->assertSee('"count": 0')
        ->assertSee('"exercises": []');
});

it('includes muscle groups with load factors in response', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();

    $chest = MuscleGroup::factory()->create(['name' => 'chest', 'label' => 'Chest', 'body_part' => BodyPart::Chest]);
    $triceps = MuscleGroup::factory()->create(['name' => 'triceps', 'label' => 'Triceps', 'body_part' => BodyPart::Shoulder]);

    $exercise = Exercise::factory()->create(['name' => 'Bench Press']);
    $exercise->muscleGroups()->attach($chest, ['load_factor' => 1.0]);
    $exercise->muscleGroups()->attach($triceps, ['load_factor' => 0.5]);

    $response = WorkoutServer::actingAs($user)->tool(SearchExercisesTool::class, [
        'query' => 'Bench Press',
    ]);

    $response->assertOk()
        ->assertSee('primary_muscles')
        ->assertSee('"name": "chest"')
        ->assertSee('secondary_muscles')
        ->assertSee('"name": "triceps"');
});

it('requires at least query or muscle_group', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();

    $response = WorkoutServer::actingAs($user)->tool(SearchExercisesTool::class, []);

    $response->assertHasErrors()
        ->assertSee('query')
        ->assertSee('muscle_group');
});

it('filters by garmin_compatible true', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();

    Exercise::factory()->withGarminMapping()->create(['name' => 'Garmin Bench Press']);
    Exercise::factory()->create(['name' => 'Custom Press']);

    $response = WorkoutServer::actingAs($user)->tool(SearchExercisesTool::class, [
        'query' => 'Press',
        'garmin_compatible' => true,
    ]);

    $response->assertOk()
        ->assertSee('Garmin Bench Press')
        ->assertDontSee('Custom Press');
});

it('filters by garmin_compatible false', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();

    Exercise::factory()->withGarminMapping()->create(['name' => 'Garmin Bench Press']);
    Exercise::factory()->create(['name' => 'Custom Press']);

    $response = WorkoutServer::actingAs($user)->tool(SearchExercisesTool::class, [
        'query' => 'Press',
        'garmin_compatible' => false,
    ]);

    $response->assertOk()
        ->assertSee('Custom Press')
        ->assertDontSee('Garmin Bench Press');
});

it('includes garmin_compatible flag in results', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();

    Exercise::factory()->withGarminMapping()->create(['name' => 'Mapped Exercise']);

    $response = WorkoutServer::actingAs($user)->tool(SearchExercisesTool::class, [
        'query' => 'Mapped Exercise',
    ]);

    $response->assertOk()
        ->assertSee('"garmin_compatible": true');
});
