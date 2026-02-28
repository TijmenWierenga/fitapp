<?php

use App\Mcp\Resources\WorkoutGuidelinesResource;
use App\Mcp\Servers\WorkoutServer;
use App\Models\User;

it('includes workout structure requirements', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();

    $response = WorkoutServer::actingAs($user)->resource(WorkoutGuidelinesResource::class, []);

    $response->assertOk()
        ->assertSee('Workout Structure')
        ->assertSee('Warm-Up')
        ->assertSee('Main Work')
        ->assertSee('Cool-Down');
});

it('includes activity-specific conventions', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();

    $response = WorkoutServer::actingAs($user)->resource(WorkoutGuidelinesResource::class, []);

    $response->assertOk()
        ->assertSee('Strength')
        ->assertSee('Running/Cardio')
        ->assertSee('Yoga/Mobility');
});

it('includes garmin fit compatibility details', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();

    $response = WorkoutServer::actingAs($user)->resource(WorkoutGuidelinesResource::class, []);

    $response->assertOk()
        ->assertSee('Garmin FIT Compatibility')
        ->assertSee('prefer_garmin_exercises')
        ->assertSee('garmin_compatible');
});

it('includes pain score scale', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();

    $response = WorkoutServer::actingAs($user)->resource(WorkoutGuidelinesResource::class, []);

    $response->assertOk()
        ->assertSee('Pain Score Scale')
        ->assertSee('No Pain')
        ->assertSee('Mild')
        ->assertSee('Moderate')
        ->assertSee('Severe');
});
