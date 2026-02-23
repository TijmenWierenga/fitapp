<?php

namespace App\Mcp\Tools;

use App\Tools\Handlers\UpdateFitnessProfileHandler;
use App\Tools\Input\UpdateFitnessProfileInput;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[IsIdempotent]
class UpdateFitnessProfileTool extends Tool
{
    public function __construct(
        private UpdateFitnessProfileHandler $handler,
    ) {}

    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Update or create a user's fitness profile. Use this to set their fitness goals,
        available training days, and session duration preferences.

        **Primary Goals:**
        - `weight_loss` - Focus on burning calories and reducing body fat
        - `muscle_gain` - Build strength and increase muscle mass
        - `endurance` - Improve cardiovascular fitness and stamina
        - `general_fitness` - Maintain overall health and well-being
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'primary_goal' => ['required', Rule::enum(\App\Enums\FitnessGoal::class)],
            'goal_details' => 'nullable|string|max:5000',
            'available_days_per_week' => 'required|integer|min:1|max:7',
            'minutes_per_session' => 'required|integer|min:15|max:180',
            'prefer_garmin_exercises' => 'nullable|boolean',
        ], [
            'primary_goal.Enum' => 'Invalid goal. Must be one of: weight_loss, muscle_gain, endurance, general_fitness.',
            'available_days_per_week.min' => 'Available days must be at least 1.',
            'available_days_per_week.max' => 'Available days cannot exceed 7.',
            'minutes_per_session.min' => 'Session duration must be at least 15 minutes.',
            'minutes_per_session.max' => 'Session duration cannot exceed 180 minutes.',
        ]);

        $result = $this->handler->execute(
            $request->user(),
            UpdateFitnessProfileInput::fromArray($validated),
        );

        return Response::structured($result->toArray());
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return $this->handler->schema($schema);
    }
}
