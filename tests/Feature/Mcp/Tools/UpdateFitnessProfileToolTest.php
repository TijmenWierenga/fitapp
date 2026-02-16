<?php

use App\Enums\FitnessGoal;
use App\Mcp\Servers\WorkoutServer;
use App\Mcp\Tools\UpdateFitnessProfileTool;
use App\Models\FitnessProfile;
use App\Models\User;

use function Pest\Laravel\assertDatabaseHas;

it('creates a fitness profile successfully', function () {
    $user = User::factory()->create();

    $response = WorkoutServer::actingAs($user)->tool(UpdateFitnessProfileTool::class, [
        'primary_goal' => 'weight_loss',
        'goal_details' => 'Lose 15kg by summer',
        'available_days_per_week' => 4,
        'minutes_per_session' => 60,
    ]);

    $response->assertOk()
        ->assertSee('weight_loss')
        ->assertSee('Fitness profile updated successfully');

    assertDatabaseHas('fitness_profiles', [
        'user_id' => $user->id,
        'primary_goal' => 'weight_loss',
        'goal_details' => 'Lose 15kg by summer',
        'available_days_per_week' => 4,
        'minutes_per_session' => 60,
    ]);
});

it('updates an existing fitness profile', function () {
    $user = User::factory()->create();
    FitnessProfile::factory()->create([
        'user_id' => $user->id,
        'primary_goal' => FitnessGoal::WeightLoss,
    ]);

    $response = WorkoutServer::actingAs($user)->tool(UpdateFitnessProfileTool::class, [
        'primary_goal' => 'muscle_gain',
        'available_days_per_week' => 6,
        'minutes_per_session' => 90,
    ]);

    $response->assertOk()
        ->assertSee('muscle_gain');

    assertDatabaseHas('fitness_profiles', [
        'user_id' => $user->id,
        'primary_goal' => 'muscle_gain',
        'available_days_per_week' => 6,
    ]);
});

it('fails with invalid primary_goal', function () {
    $user = User::factory()->create();

    $response = WorkoutServer::actingAs($user)->tool(UpdateFitnessProfileTool::class, [
        'primary_goal' => 'invalid_goal',
        'available_days_per_week' => 4,
        'minutes_per_session' => 60,
    ]);

    $response->assertHasErrors();
});

it('fails with days out of range', function (int $days) {
    $user = User::factory()->create();

    $response = WorkoutServer::actingAs($user)->tool(UpdateFitnessProfileTool::class, [
        'primary_goal' => 'weight_loss',
        'available_days_per_week' => $days,
        'minutes_per_session' => 60,
    ]);

    $response->assertHasErrors();
})->with([0, 8, -1, 100]);

it('fails with minutes out of range', function (int $minutes) {
    $user = User::factory()->create();

    $response = WorkoutServer::actingAs($user)->tool(UpdateFitnessProfileTool::class, [
        'primary_goal' => 'weight_loss',
        'available_days_per_week' => 4,
        'minutes_per_session' => $minutes,
    ]);

    $response->assertHasErrors();
})->with([10, 14, 181, 500]);

it('sets prefer_garmin_exercises preference', function () {
    $user = User::factory()->create();

    $response = WorkoutServer::actingAs($user)->tool(UpdateFitnessProfileTool::class, [
        'primary_goal' => 'general_fitness',
        'available_days_per_week' => 4,
        'minutes_per_session' => 60,
        'prefer_garmin_exercises' => true,
    ]);

    $response->assertOk()
        ->assertSee('"prefer_garmin_exercises": true');

    assertDatabaseHas('fitness_profiles', [
        'user_id' => $user->id,
        'prefer_garmin_exercises' => true,
    ]);
});

it('defaults prefer_garmin_exercises to false when not provided', function () {
    $user = User::factory()->create();

    WorkoutServer::actingAs($user)->tool(UpdateFitnessProfileTool::class, [
        'primary_goal' => 'general_fitness',
        'available_days_per_week' => 3,
        'minutes_per_session' => 45,
    ]);

    assertDatabaseHas('fitness_profiles', [
        'user_id' => $user->id,
        'prefer_garmin_exercises' => false,
    ]);
});
