<?php

use App\Enums\BodyPart;
use App\Enums\InjuryType;
use App\Mcp\Prompts\PlanTrainingProgramPrompt;
use App\Mcp\Servers\WorkoutServer;
use App\Models\FitnessProfile;
use App\Models\Injury;
use App\Models\User;
use App\Models\Workout;

it('returns program context with profile and instructions', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();
    FitnessProfile::factory()->for($user)->endurance()->create([
        'available_days_per_week' => 5,
        'minutes_per_session' => 45,
    ]);

    $response = WorkoutServer::actingAs($user)->prompt(PlanTrainingProgramPrompt::class, [
        'program_type' => '5k training',
        'duration_weeks' => '8',
    ]);

    $response->assertOk()
        ->assertSee('8-week')
        ->assertSee('5k training')
        ->assertSee('Endurance')
        ->assertSee('Progressive overload');
});

it('includes active injuries with program awareness', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();
    Injury::factory()->for($user)->active()->create([
        'body_part' => BodyPart::Ankle,
        'injury_type' => InjuryType::Chronic,
    ]);

    $response = WorkoutServer::actingAs($user)->prompt(PlanTrainingProgramPrompt::class, [
        'program_type' => 'strength program',
        'duration_weeks' => '4',
    ]);

    $response->assertOk()
        ->assertSee('Active Injuries')
        ->assertSee('ankle')
        ->assertSee('considered throughout the entire program');
});

it('shows existing workouts in program timeframe', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();
    Workout::factory()->for($user)->create([
        'name' => 'Existing Yoga',
        'scheduled_at' => now()->next('Wednesday'),
    ]);

    $response = WorkoutServer::actingAs($user)->prompt(PlanTrainingProgramPrompt::class, [
        'program_type' => 'general fitness',
        'duration_weeks' => '2',
        'start_date' => now()->next('Monday')->format('Y-m-d'),
    ]);

    $response->assertOk()
        ->assertSee('Existing Yoga');
});

it('validates required arguments', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();

    $response = WorkoutServer::actingAs($user)->prompt(PlanTrainingProgramPrompt::class, [
        'program_type' => '5k training',
    ]);

    $response->assertHasErrors(['duration weeks']);
});

it('handles missing fitness profile', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();

    $response = WorkoutServer::actingAs($user)->prompt(PlanTrainingProgramPrompt::class, [
        'program_type' => 'strength program',
        'duration_weeks' => '6',
    ]);

    $response->assertOk()
        ->assertSee('No fitness profile set up');
});

it('defaults start date to next Monday', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();

    $response = WorkoutServer::actingAs($user)->prompt(PlanTrainingProgramPrompt::class, [
        'program_type' => 'general fitness',
        'duration_weeks' => '4',
    ]);

    $nextMonday = now($user->getTimezoneObject())->next('Monday');

    $response->assertOk()
        ->assertSee($nextMonday->format('M j, Y'));
});
