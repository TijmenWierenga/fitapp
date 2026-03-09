<?php

namespace App\Mcp\Tools;

use App\Enums\BiologicalSex;
use App\Enums\Equipment;
use App\Enums\ExperienceLevel;
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
        available training days, session duration preferences, physical attributes, and equipment availability.

        **Primary Goals:**
        - `weight_loss` - Focus on burning calories and reducing body fat
        - `muscle_gain` - Build strength and increase muscle mass
        - `endurance` - Improve cardiovascular fitness and stamina
        - `general_fitness` - Maintain overall health and well-being
        - `sports_performance` - Improve performance in a specific sport
        - `injury_recovery` - Rehabilitate and return to full activity
        - `flexibility` - Improve mobility, flexibility, and movement quality
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
            'experience_level' => ['nullable', Rule::enum(ExperienceLevel::class)],
            'date_of_birth' => 'nullable|date|before:today',
            'biological_sex' => ['nullable', Rule::enum(BiologicalSex::class)],
            'body_weight_kg' => 'nullable|numeric|min:20|max:300',
            'height_cm' => 'nullable|integer|min:100|max:250',
            'has_gym_access' => 'nullable|boolean',
            'home_equipment' => 'nullable|array',
            'home_equipment.*' => [Rule::enum(Equipment::class)],
        ], [
            'primary_goal.Enum' => 'Invalid goal. Must be one of: weight_loss, muscle_gain, endurance, general_fitness, sports_performance, injury_recovery, flexibility.',
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
