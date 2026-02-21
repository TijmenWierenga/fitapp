<?php

use App\Ai\Agents\FitnessCoach;
use App\Ai\Tools\CreateWorkoutTool;
use App\Ai\Tools\GetWorkloadTool;
use App\Ai\Tools\SearchExercisesTool;
use App\Models\FitnessProfile;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('can be instantiated', function () {
    $agent = FitnessCoach::make();

    expect($agent)->toBeInstanceOf(FitnessCoach::class);
});

it('registers all 15 tools', function () {
    $agent = FitnessCoach::make();
    $tools = $agent->tools();

    expect($tools)->toHaveCount(15);

    $toolClasses = array_map(fn (object $tool): string => $tool::class, $tools);

    expect($toolClasses)
        ->toContain(CreateWorkoutTool::class)
        ->toContain(SearchExercisesTool::class)
        ->toContain(GetWorkloadTool::class);
});

it('includes user context in instructions when authenticated', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    FitnessProfile::factory()->muscleGain()->for($user)->create();

    $this->actingAs($user);

    $agent = FitnessCoach::make();
    $instructions = $agent->instructions();

    expect($instructions)
        ->toContain('fitness coach')
        ->toContain('Current User Context')
        ->toContain('Muscle Gain');
});

it('provides base instructions without user when not authenticated', function () {
    $agent = FitnessCoach::make();
    $instructions = $agent->instructions();

    expect($instructions)
        ->toContain('fitness coach')
        ->not->toContain('Current User Context');
});

it('responds when faked', function () {
    FitnessCoach::fake(['I can help you with that workout!']);

    $response = FitnessCoach::make()->prompt('Help me plan a workout');

    expect($response->text)->toBe('I can help you with that workout!');

    FitnessCoach::assertPrompted('Help me plan a workout');
});
