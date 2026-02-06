<?php

use App\Mcp\Servers\WorkoutServer;
use App\Mcp\Tools\GetExerciseCatalogTool;
use App\Models\Exercise;
use App\Models\ExerciseMuscleLoad;
use App\Models\User;

it('returns all exercises when no filters provided', function () {
    $user = User::factory()->create();
    Exercise::factory()->count(3)->create();

    $response = WorkoutServer::actingAs($user)->tool(GetExerciseCatalogTool::class, []);

    $response->assertOk()
        ->assertSee('"count":3');
});

it('filters by category', function () {
    $user = User::factory()->create();
    Exercise::factory()->create(['category' => 'compound']);
    Exercise::factory()->create(['category' => 'isolation']);

    $response = WorkoutServer::actingAs($user)->tool(GetExerciseCatalogTool::class, [
        'category' => 'compound',
    ]);

    $response->assertOk()
        ->assertSee('"count":1')
        ->assertSee('compound');
});

it('filters by equipment', function () {
    $user = User::factory()->create();
    Exercise::factory()->create(['equipment' => 'barbell']);
    Exercise::factory()->create(['equipment' => 'dumbbell']);

    $response = WorkoutServer::actingAs($user)->tool(GetExerciseCatalogTool::class, [
        'equipment' => 'barbell',
    ]);

    $response->assertOk()
        ->assertSee('"count":1');
});

it('filters by muscle group', function () {
    $user = User::factory()->create();
    $exercise = Exercise::factory()->create();
    ExerciseMuscleLoad::create([
        'exercise_id' => $exercise->id,
        'muscle_group' => 'chest',
        'role' => 'primary',
        'load_factor' => 1.0,
    ]);

    $otherExercise = Exercise::factory()->create();
    ExerciseMuscleLoad::create([
        'exercise_id' => $otherExercise->id,
        'muscle_group' => 'quadriceps',
        'role' => 'primary',
        'load_factor' => 1.0,
    ]);

    $response = WorkoutServer::actingAs($user)->tool(GetExerciseCatalogTool::class, [
        'muscle_group' => 'chest',
    ]);

    $response->assertOk()
        ->assertSee('"count":1')
        ->assertSee($exercise->name);
});

it('searches by name', function () {
    $user = User::factory()->create();
    Exercise::factory()->create(['name' => 'Barbell Bench Press']);
    Exercise::factory()->create(['name' => 'Squat']);

    $response = WorkoutServer::actingAs($user)->tool(GetExerciseCatalogTool::class, [
        'search' => 'bench',
    ]);

    $response->assertOk()
        ->assertSee('"count":1')
        ->assertSee('Barbell Bench Press');
});

it('returns muscle loads with exercises', function () {
    $user = User::factory()->create();
    $exercise = Exercise::factory()->create();
    ExerciseMuscleLoad::create([
        'exercise_id' => $exercise->id,
        'muscle_group' => 'chest',
        'role' => 'primary',
        'load_factor' => 1.0,
    ]);

    $response = WorkoutServer::actingAs($user)->tool(GetExerciseCatalogTool::class, []);

    $response->assertOk()
        ->assertSee('muscle_loads')
        ->assertSee('chest');
});

it('fails with invalid category', function () {
    $user = User::factory()->create();

    $response = WorkoutServer::actingAs($user)->tool(GetExerciseCatalogTool::class, [
        'category' => 'invalid',
    ]);

    $response->assertHasErrors()
        ->assertSee('category');
});
