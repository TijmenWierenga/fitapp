<?php

use App\Enums\BodyPart;
use App\Enums\InjuryType;
use App\Mcp\Prompts\AssessInjuryPrompt;
use App\Mcp\Servers\WorkoutServer;
use App\Models\Injury;
use App\Models\User;
use App\Models\Workout;

it('returns the 5-step assessment protocol', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();

    $response = WorkoutServer::actingAs($user)->prompt(AssessInjuryPrompt::class);

    $response->assertOk()
        ->assertSee('Step 1: Location')
        ->assertSee('Step 2: Duration')
        ->assertSee('Step 3: Progression')
        ->assertSee('Step 4: Pain Characteristics')
        ->assertSee('Step 5: Professional Consultation');
});

it('includes red flags section', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();

    $response = WorkoutServer::actingAs($user)->prompt(AssessInjuryPrompt::class);

    $response->assertOk()
        ->assertSee('Red Flags')
        ->assertSee('DO NOT PROCEED')
        ->assertSee('healthcare professional');
});

it('includes existing injury history', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();
    Injury::factory()->for($user)->active()->create([
        'body_part' => BodyPart::Shoulder,
        'injury_type' => InjuryType::Chronic,
    ]);
    Injury::factory()->for($user)->resolved()->create([
        'body_part' => BodyPart::Knee,
        'injury_type' => InjuryType::Acute,
    ]);

    $response = WorkoutServer::actingAs($user)->prompt(AssessInjuryPrompt::class);

    $response->assertOk()
        ->assertSee('Injury History')
        ->assertSee('shoulder')
        ->assertSee('knee')
        ->assertSee('ACTIVE')
        ->assertSee('resolved');
});

it('shows no injury history when none exist', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();

    $response = WorkoutServer::actingAs($user)->prompt(AssessInjuryPrompt::class);

    $response->assertOk()
        ->assertSee('No previous injuries recorded');
});

it('includes recent workouts as potential cause context', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();
    Workout::factory()->for($user)->completed()->create([
        'name' => 'Heavy Deadlifts',
        'completed_at' => now()->subDay(),
    ]);

    $response = WorkoutServer::actingAs($user)->prompt(AssessInjuryPrompt::class);

    $response->assertOk()
        ->assertSee('Heavy Deadlifts')
        ->assertSee('potential cause context');
});

it('handles no recent workouts', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();

    $response = WorkoutServer::actingAs($user)->prompt(AssessInjuryPrompt::class);

    $response->assertOk()
        ->assertSee('No completed workouts in the last 7 days');
});
