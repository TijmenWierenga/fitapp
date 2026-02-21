<?php

namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class GetWorkoutScheduleTool implements Tool
{
    public function description(): string
    {
        return 'Get the user\'s workout schedule showing upcoming and recently completed workouts. Use this to understand what the user has planned and recently done before suggesting new workouts.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'upcoming_limit' => $schema->integer()->description('Number of upcoming workouts to return (default: 20, max: 50)')->nullable(),
            'completed_limit' => $schema->integer()->description('Number of completed workouts to return (default: 10, max: 50)')->nullable(),
        ];
    }

    public function handle(Request $request): string
    {
        $user = auth()->user();

        $upcomingLimit = min($request['upcoming_limit'] ?? 20, 50);
        $completedLimit = min($request['completed_limit'] ?? 10, 50);

        $upcoming = $user->workouts()->upcoming()->withCount('sections')->limit($upcomingLimit)->get();
        $completed = $user->workouts()->completed()->withCount('sections')->limit($completedLimit)->get();

        $upcomingData = $upcoming->map(fn ($workout): array => [
            'id' => $workout->id,
            'name' => $workout->name,
            'activity' => $workout->activity->value,
            'scheduled_at' => $user->toUserTimezone($workout->scheduled_at)->toIso8601String(),
            'sections_count' => $workout->sections_count,
            'notes' => $workout->notes,
        ]);

        $completedData = $completed->map(fn ($workout): array => [
            'id' => $workout->id,
            'name' => $workout->name,
            'activity' => $workout->activity->value,
            'completed_at' => $user->toUserTimezone($workout->completed_at)->toIso8601String(),
            'rpe' => $workout->rpe,
            'feeling' => $workout->feeling,
        ]);

        return json_encode([
            'upcoming' => $upcomingData->toArray(),
            'completed' => $completedData->toArray(),
        ]);
    }
}
