<?php

namespace App\Mcp\Tools;

use App\Models\User;
use App\Services\Training\TrainingAnalyticsService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class GetTrainingAnalyticsTool extends Tool
{
    public function __construct(
        protected TrainingAnalyticsService $analyticsService
    ) {}

    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Get aggregated training analytics for a user over a specified period.

        Returns total workouts completed, workouts per week, completion rate,
        average RPE and feeling, activity distribution, and current streak.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'weeks' => 'nullable|integer|min:1|max:12',
        ], [
            'user_id.exists' => 'User not found. Please provide a valid user ID.',
        ]);

        $user = User::findOrFail($validated['user_id']);
        $weeks = $validated['weeks'] ?? 4;

        $analytics = $this->analyticsService->getAnalytics($user, $weeks);

        return Response::text(json_encode([
            'success' => true,
            ...$analytics,
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
            'user_id' => $schema->integer()->description('The ID of the user'),
            'weeks' => $schema->integer()->description('Number of weeks to analyze (default: 4, max: 12)')->nullable(),
        ];
    }
}
