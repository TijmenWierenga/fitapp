<?php

use App\Mcp\Servers\WorkoutPlannerServer;
use App\Mcp\Tools\CreateWorkoutTool;
use App\Models\User;

test('creates workout with all fields including notes', function () {
    $user = User::factory()->create(['timezone' => 'America/New_York']);

    $response = WorkoutPlannerServer::actingAs($user)
        ->tool(CreateWorkoutTool::class, [
            'name' => 'Morning Run',
            'sport' => 'running',
            'notes' => 'Easy 5k at conversational pace. Focus on maintaining good form and breathing.',
            'scheduled_at' => '2026-02-01T08:00:00',
        ]);

    $response->assertOk();

    expect($user->workouts()->count())->toBe(1);

    $workout = $user->workouts()->first();
    expect($workout->name)->toBe('Morning Run');
    expect($workout->sport->value)->toBe('running');
    expect($workout->notes)->toContain('conversational pace');
});

test('requires authentication', function () {
    $response = WorkoutPlannerServer::tool(CreateWorkoutTool::class, [
        'name' => 'Test',
        'sport' => 'running',
        'scheduled_at' => '2026-02-01T08:00:00',
    ]);

    $response->assertHasErrors();
});

test('validates required fields', function () {
    $user = User::factory()->create();

    $response = WorkoutPlannerServer::actingAs($user)
        ->tool(CreateWorkoutTool::class, []);

    $response->assertHasErrors();
});

test('validates sport enum', function () {
    $user = User::factory()->create();

    $response = WorkoutPlannerServer::actingAs($user)
        ->tool(CreateWorkoutTool::class, [
            'name' => 'Test',
            'sport' => 'invalid_sport',
            'scheduled_at' => '2026-02-01T08:00:00',
        ]);

    $response->assertHasErrors();
});
