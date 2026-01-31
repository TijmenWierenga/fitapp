<?php

namespace App\Mcp\Tools;

use App\Data\UpdateWorkoutData;
use App\Enums\Workout\Activity;
use App\Services\Workout\WorkoutService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class UpdateWorkoutTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Update an existing workout. Only workouts that have not been completed can be updated.

        You can update the name, activity, scheduled time, or notes. Only provide the fields you want to change.
    MARKDOWN;

    public function __construct(
        protected WorkoutService $workoutService
    ) {}

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'workout_id' => 'required|integer',
            'name' => 'sometimes|string|max:255',
            'activity' => ['sometimes', Rule::enum(Activity::class)],
            'scheduled_at' => 'sometimes|date',
            'notes' => 'nullable|string|max:5000',
        ], [
            'activity.Enum' => 'Invalid activity type. See available activity values.',
            'scheduled_at.date' => 'Please provide a valid date and time.',
        ]);

        $user = $request->user();

        $workout = $this->workoutService->find($user, $validated['workout_id']);

        if (! $workout) {
            return Response::error('Workout not found or access denied');
        }

        $scheduledAt = null;
        if (isset($validated['scheduled_at'])) {
            $scheduledAt = Carbon::parse($validated['scheduled_at'], $user->getTimezoneObject())->utc();
        }

        $data = new UpdateWorkoutData(
            name: $validated['name'] ?? null,
            activity: isset($validated['activity']) ? Activity::from($validated['activity']) : null,
            scheduledAt: $scheduledAt,
            notes: $validated['notes'] ?? null,
            updateNotes: array_key_exists('notes', $validated),
        );

        try {
            $workout = $this->workoutService->update($user, $workout, $data);
        } catch (AuthorizationException) {
            return Response::error('Cannot update completed workouts');
        }

        return Response::text(json_encode([
            'success' => true,
            'workout' => [
                'id' => $workout->id,
                'name' => $workout->name,
                'activity' => $workout->activity->value,
                'scheduled_at' => $user->toUserTimezone($workout->scheduled_at)->toIso8601String(),
                'notes' => $workout->notes,
                'completed' => $workout->isCompleted(),
            ],
            'message' => 'Workout updated successfully',
        ]));
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'workout_id' => $schema->integer()->description('The ID of the workout to update'),
            'name' => $schema->string()->description('The new name for the workout')->nullable(),
            'activity' => $schema->string()->description('The new activity type (e.g., run, strength, cardio, hiit, bike, pool_swim, hike, yoga, etc.)')->nullable(),
            'scheduled_at' => $schema->string()->description('The new scheduled date and time (in user\'s timezone)')->nullable(),
            'notes' => $schema->string()->description('The new notes for the workout')->nullable(),
        ];
    }
}
