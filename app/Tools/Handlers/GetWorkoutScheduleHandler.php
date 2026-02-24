<?php

declare(strict_types=1);

namespace App\Tools\Handlers;

use App\Models\User;
use App\Tools\Input\GetWorkoutScheduleInput;
use App\Tools\ToolResult;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class GetWorkoutScheduleHandler
{
    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'upcoming_days' => $schema->integer()->description('Number of days ahead to look for upcoming workouts (default: 14, max: 90)')->nullable(),
            'completed_days' => $schema->integer()->description('Number of days back to look for completed workouts (default: 7, max: 90)')->nullable(),
        ];
    }

    public function execute(User $user, GetWorkoutScheduleInput $input): ToolResult
    {
        $upcoming = $user->workouts()->upcoming()
            ->where('scheduled_at', '<=', now()->addDays($input->upcomingDays))
            ->withCount('sections')
            ->limit(50)
            ->get();

        $completed = $user->workouts()->completed()
            ->where('completed_at', '>=', now()->subDays($input->completedDays))
            ->withCount('sections')
            ->limit(50)
            ->get();

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

        return ToolResult::success([
            'upcoming' => $upcomingData->toArray(),
            'completed' => $completedData->toArray(),
        ]);
    }
}
