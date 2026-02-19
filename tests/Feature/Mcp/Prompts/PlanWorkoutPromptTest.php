<?php

use App\Enums\BodyPart;
use App\Enums\InjuryType;
use App\Mcp\Prompts\PlanWorkoutPrompt;
use App\Mcp\Servers\WorkoutServer;
use App\Models\FitnessProfile;
use App\Models\Injury;
use App\Models\User;
use App\Models\Workout;

it('returns workout context with fitness profile', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();
    FitnessProfile::factory()->for($user)->muscleGain()->create([
        'available_days_per_week' => 4,
        'minutes_per_session' => 60,
    ]);

    $response = WorkoutServer::actingAs($user)->prompt(PlanWorkoutPrompt::class, [
        'activity' => 'strength',
    ]);

    $response->assertOk()
        ->assertSee('Muscle Gain')
        ->assertSee('Available days/week')
        ->assertSee('Strength');
});

it('includes active injuries in context', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();
    Injury::factory()->for($user)->active()->create([
        'body_part' => BodyPart::Knee,
        'injury_type' => InjuryType::Acute,
    ]);

    $response = WorkoutServer::actingAs($user)->prompt(PlanWorkoutPrompt::class);

    $response->assertOk()
        ->assertSee('Active Injuries')
        ->assertSee('knee');
});

it('includes schedule context with upcoming and completed workouts', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();
    Workout::factory()->for($user)->create([
        'name' => 'Upcoming Run',
        'scheduled_at' => now()->addDays(2),
    ]);
    Workout::factory()->for($user)->completed()->create([
        'name' => 'Past Strength',
        'completed_at' => now()->subDay(),
    ]);

    $response = WorkoutServer::actingAs($user)->prompt(PlanWorkoutPrompt::class);

    $response->assertOk()
        ->assertSee('Upcoming Run')
        ->assertSee('Past Strength');
});

it('handles missing fitness profile gracefully', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();

    $response = WorkoutServer::actingAs($user)->prompt(PlanWorkoutPrompt::class);

    $response->assertOk()
        ->assertSee('No fitness profile set up');
});

it('uses recommendation flow when no activity specified', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();
    FitnessProfile::factory()->for($user)->generalFitness()->create();

    $response = WorkoutServer::actingAs($user)->prompt(PlanWorkoutPrompt::class);

    $response->assertOk()
        ->assertSee('recommend the best workout type');
});

it('uses specific activity flow when activity is provided', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();

    $response = WorkoutServer::actingAs($user)->prompt(PlanWorkoutPrompt::class, [
        'activity' => 'run',
    ]);

    $response->assertOk()
        ->assertSee('Run')
        ->assertDontSee('recommend the best workout type');
});

it('accepts a custom date', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();

    $response = WorkoutServer::actingAs($user)->prompt(PlanWorkoutPrompt::class, [
        'date' => '2026-03-15',
    ]);

    $response->assertOk()
        ->assertSee('2026-03-15');
});
