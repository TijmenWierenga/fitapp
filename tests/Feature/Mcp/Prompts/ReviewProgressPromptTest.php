<?php

use App\Enums\BodyPart;
use App\Enums\InjuryType;
use App\Enums\Workout\Activity;
use App\Mcp\Prompts\ReviewProgressPrompt;
use App\Mcp\Servers\WorkoutServer;
use App\Models\FitnessProfile;
use App\Models\Injury;
use App\Models\User;
use App\Models\Workout;

it('returns progress analysis with completed workouts', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();
    FitnessProfile::factory()->for($user)->muscleGain()->create();
    Workout::factory()->for($user)->strength()->completed()->count(5)->create([
        'completed_at' => now()->subDays(3),
    ]);

    $response = WorkoutServer::actingAs($user)->prompt(ReviewProgressPrompt::class);

    $response->assertOk()
        ->assertSee('Completed Workouts')
        ->assertSee('5 in last 28 days')
        ->assertSee('Muscle Gain')
        ->assertSee('Analysis Framework');
});

it('includes workload data when available', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();
    Workout::factory()->for($user)->completed()->create([
        'completed_at' => now()->subDay(),
    ]);

    $response = WorkoutServer::actingAs($user)->prompt(ReviewProgressPrompt::class);

    $response->assertOk()
        ->assertSee('Workload Summary');
});

it('includes active injuries', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();
    Injury::factory()->for($user)->active()->create([
        'body_part' => BodyPart::LowerBack,
        'injury_type' => InjuryType::Chronic,
    ]);

    $response = WorkoutServer::actingAs($user)->prompt(ReviewProgressPrompt::class);

    $response->assertOk()
        ->assertSee('Active Injuries')
        ->assertSee('lower_back');
});

it('uses custom timeframe', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();

    $response = WorkoutServer::actingAs($user)->prompt(ReviewProgressPrompt::class, [
        'timeframe_days' => '14',
    ]);

    $response->assertOk()
        ->assertSee('last 14 days');
});

it('defaults to 28 days timeframe', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();

    $response = WorkoutServer::actingAs($user)->prompt(ReviewProgressPrompt::class);

    $response->assertOk()
        ->assertSee('last 28 days');
});

it('groups completed workouts by activity', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();
    Workout::factory()->for($user)->strength()->completed()->count(3)->create([
        'completed_at' => now()->subDays(2),
    ]);
    Workout::factory()->for($user)->completed()->count(2)->create([
        'activity' => Activity::Run,
        'completed_at' => now()->subDays(2),
    ]);

    $response = WorkoutServer::actingAs($user)->prompt(ReviewProgressPrompt::class);

    $response->assertOk()
        ->assertSee('By Activity')
        ->assertSee('Strength')
        ->assertSee('Run');
});

it('handles no completed workouts in timeframe', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();

    $response = WorkoutServer::actingAs($user)->prompt(ReviewProgressPrompt::class);

    $response->assertOk()
        ->assertSee('No completed workouts in the last 28 days');
});

it('handles missing fitness profile', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();

    $response = WorkoutServer::actingAs($user)->prompt(ReviewProgressPrompt::class);

    $response->assertOk()
        ->assertSee('No fitness profile set up');
});
