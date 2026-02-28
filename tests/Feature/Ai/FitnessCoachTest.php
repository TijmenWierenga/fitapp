<?php

use App\Ai\Agents\FitnessCoach;
use App\Ai\Tools\CreateWorkoutTool;
use App\Ai\Tools\RefreshUserContextTool;
use App\Ai\Tools\SearchExercisesTool;
use App\Models\User;

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

it('provides instructions that include current date and reference refresh-user-context', function () {
    $user = User::factory()->withTimezone('Europe/Amsterdam')->create();
    $this->actingAs($user);

    $agent = FitnessCoach::make();
    $instructions = $agent->instructions();

    $now = $user->currentTimeInTimezone();

    expect($instructions)
        ->toContain('fitness coach')
        ->toContain('Core Behaviors')
        ->toContain('refresh-user-context')
        ->toContain($now->format('Y-m-d'))
        ->toContain($now->format('l'))
        ->toContain('Europe/Amsterdam');
});

it('responds when faked', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    $this->actingAs($user);

    FitnessCoach::fake(['I can help you with that workout!']);

    $response = FitnessCoach::make()->prompt('Help me plan a workout');

    expect($response->text)->toBe('I can help you with that workout!');

    FitnessCoach::assertPrompted('Help me plan a workout');
});
