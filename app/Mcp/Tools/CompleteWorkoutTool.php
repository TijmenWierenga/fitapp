<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ResolvesUser;
use App\Models\Workout;
use App\Services\Workout\WorkoutService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class CompleteWorkoutTool extends Tool
{
    use ResolvesUser;

    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Mark a workout as completed with RPE and feeling ratings.

        **RPE (Rate of Perceived Exertion):** 1-10 scale
        - 1-2: Very Easy
        - 3-4: Easy
        - 5-6: Moderate
        - 7-8: Hard
        - 9-10: Maximum Effort

        **Feeling:** 1-5 scale (post-workout feeling)
        - 1: Terrible
        - 2: Poor
        - 3: Average
        - 4: Good
        - 5: Great
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
            'workout_id' => 'required|integer',
            'rpe' => 'required|integer|min:1|max:10',
            'feeling' => 'required|integer|min:1|max:5',
        ], [
            'user_id.exists' => 'User not found. Please provide a valid user ID.',
            'rpe.min' => 'RPE must be between 1 (very easy) and 10 (maximum effort)',
            'rpe.max' => 'RPE must be between 1 (very easy) and 10 (maximum effort)',
            'feeling.min' => 'Feeling must be between 1 and 5',
            'feeling.max' => 'Feeling must be between 1 and 5',
        ]);

        $user = $this->resolveUser($request);

        $workout = $this->workoutService->find($user, $validated['workout_id']);

        if (! $workout) {
            return Response::error('Workout not found or access denied');
        }

        try {
            $workout = $this->workoutService->complete($user, $workout, $validated['rpe'], $validated['feeling']);
        } catch (AuthorizationException) {
            return Response::error('Workout is already completed');
        }

        return Response::text(json_encode([
            'success' => true,
            'workout' => [
                'id' => $workout->id,
                'name' => $workout->name,
                'completed_at' => $user->toUserTimezone($workout->completed_at)->toIso8601String(),
                'rpe' => $workout->rpe,
                'rpe_label' => Workout::getRpeLabel($workout->rpe),
                'feeling' => $workout->feeling,
            ],
            'message' => 'Workout completed successfully',
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
            'workout_id' => $schema->integer()->description('The ID of the workout to complete'),
            'rpe' => $schema->integer()->description('Rate of Perceived Exertion (1-10): 1-2=Very Easy, 3-4=Easy, 5-6=Moderate, 7-8=Hard, 9-10=Maximum'),
            'feeling' => $schema->integer()->description('Post-workout feeling (1-5): 1=Terrible, 2=Poor, 3=Average, 4=Good, 5=Great'),
        ];
    }
}
