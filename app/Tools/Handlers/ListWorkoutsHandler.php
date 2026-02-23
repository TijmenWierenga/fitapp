<?php

declare(strict_types=1);

namespace App\Tools\Handlers;

use App\Models\User;
use App\Models\Workout;
use App\Tools\Input\ListWorkoutsInput;
use App\Tools\ToolResult;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class ListWorkoutsHandler
{
    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'filter' => $schema->string()->enum(['upcoming', 'completed', 'overdue', 'all'])->description('Filter workouts (default: all).')->nullable(),
            'limit' => $schema->integer()->description('Maximum number of workouts to return (default: 20, max: 100)')->nullable(),
        ];
    }

    public function execute(User $user, ListWorkoutsInput $input): ToolResult
    {
        $query = $user->workouts()->withCount('sections');

        match ($input->filter) {
            'upcoming' => $query->upcoming(),
            'completed' => $query->completed(),
            'overdue' => $query->overdue(),
            default => $query->orderBy('scheduled_at', 'desc'),
        };

        $workouts = $query->limit($input->limit)->get();

        $workoutData = $workouts->map(fn (Workout $workout): array => [
            'id' => $workout->id,
            'name' => $workout->name,
            'activity' => $workout->activity->value,
            'scheduled_at' => $user->toUserTimezone($workout->scheduled_at)->toIso8601String(),
            'completed' => $workout->isCompleted(),
            'completed_at' => $workout->completed_at ? $user->toUserTimezone($workout->completed_at)->toIso8601String() : null,
            'notes' => $workout->notes,
            'sections_count' => $workout->sections_count,
        ]);

        return ToolResult::success([
            'filter' => $input->filter,
            'count' => $workoutData->count(),
            'workouts' => $workoutData->toArray(),
        ]);
    }
}
