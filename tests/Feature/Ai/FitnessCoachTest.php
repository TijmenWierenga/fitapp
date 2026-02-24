<?php

use App\Ai\Agents\FitnessCoach;
use App\Ai\Tools\CreateWorkoutTool;
use App\Ai\Tools\RefreshUserContextTool;
use App\Ai\Tools\SearchExercisesTool;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('can be instantiated', function () {
    $agent = FitnessCoach::make();

    expect($agent)->toBeInstanceOf(FitnessCoach::class);
});

it('registers 12 tools', function () {
    $agent = FitnessCoach::make();
    $tools = $agent->tools();

    expect($tools)->toHaveCount(12);

    $toolClasses = array_map(fn (object $tool): string => $tool::class, $tools);

    expect($toolClasses)
        ->toContain(CreateWorkoutTool::class)
        ->toContain(SearchExercisesTool::class)
        ->toContain(RefreshUserContextTool::class);
});

it('provides static instructions that reference refresh-user-context', function () {
    $agent = FitnessCoach::make();
    $instructions = $agent->instructions();

    expect($instructions)
        ->toContain('fitness coach')
        ->toContain('Core Behaviors')
        ->toContain('refresh-user-context');
});

it('responds when faked', function () {
    FitnessCoach::fake(['I can help you with that workout!']);

    $response = FitnessCoach::make()->prompt('Help me plan a workout');

    expect($response->text)->toBe('I can help you with that workout!');

    FitnessCoach::assertPrompted('Help me plan a workout');
});
