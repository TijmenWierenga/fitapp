<?php

namespace App\Mcp\Tools;

use App\Data\CreateWorkoutData;
use App\Enums\Workout\Activity;
use App\Mcp\Concerns\ResolvesUser;
use App\Services\Workout\WorkoutService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class CreateWorkoutTool extends Tool
{
    use ResolvesUser;

    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Create a new workout with a scheduled date and time. The workout will be created for the specified user.

        Activity types include: run, strength, cardio, hiit, bike, pool_swim, hike, yoga, and many more Garmin-compatible activities

        Dates/times should be in the user's local timezone and will be converted to UTC for storage.
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
            'user_id' => 'nullable|integer|exists:users,id',
            'name' => 'required|string|max:255',
            'activity' => ['required', Rule::enum(Activity::class)],
            'scheduled_at' => 'required|date',
            'notes' => 'nullable|string|max:5000',
        ], [
            'user_id.exists' => 'User not found. Please provide a valid user ID.',
            'activity.Enum' => 'Invalid activity type. See available activity values.',
            'scheduled_at.date' => 'Please provide a valid date and time.',
        ]);

        $user = $this->resolveUser($request);

        $scheduledAt = Carbon::parse($validated['scheduled_at'], $user->getTimezoneObject())->utc();

        $data = new CreateWorkoutData(
            name: $validated['name'],
            activity: Activity::from($validated['activity']),
            scheduledAt: $scheduledAt,
            notes: $validated['notes'] ?? null,
        );

        $workout = $this->workoutService->create($user, $data);

        return Response::text(json_encode([
            'success' => true,
            'workout' => [
                'id' => $workout->id,
                'name' => $workout->name,
                'activity' => $workout->activity->value,
                'scheduled_at' => $user->toUserTimezone($workout->scheduled_at)->toIso8601String(),
                'notes' => $workout->notes,
                'completed' => false,
            ],
            'message' => 'Workout created successfully',
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
            'user_id' => $schema->integer()->description('User ID (required for local MCP, ignored for authenticated web requests)')->nullable(),
            'name' => $schema->string()->description('The name/title of the workout (e.g., "Morning Run", "Leg Day")'),
            'activity' => $schema->string()->description('The activity type (e.g., run, strength, cardio, hiit, bike, pool_swim, hike, yoga, etc.)'),
            'scheduled_at' => $schema->string()->description('The date and time when the workout is scheduled (in user\'s timezone)'),
            'notes' => $schema->string()->description('Optional Markdown notes for the workout. Include a detailed plan: equipment needed, step-by-step phases (warm-up, main work, cool-down), sets/reps/intensity, and rest periods where applicable.')->nullable(),
        ];
    }
}
