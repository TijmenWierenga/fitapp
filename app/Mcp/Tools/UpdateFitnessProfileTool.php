<?php

namespace App\Mcp\Tools;

use App\Data\FitnessProfileData;
use App\Enums\FitnessGoal;
use App\Services\FitnessProfile\FitnessProfileService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class UpdateFitnessProfileTool extends Tool
{
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

    public function __construct(
        protected FitnessProfileService $fitnessProfileService
    ) {}

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'primary_goal' => ['required', Rule::enum(FitnessGoal::class)],
            'goal_details' => 'nullable|string|max:5000',
            'available_days_per_week' => 'required|integer|min:1|max:7',
            'minutes_per_session' => 'required|integer|min:15|max:180',
        ], [
            'primary_goal.Enum' => 'Invalid goal. Must be one of: weight_loss, muscle_gain, endurance, general_fitness.',
            'available_days_per_week.min' => 'Available days must be at least 1.',
            'available_days_per_week.max' => 'Available days cannot exceed 7.',
            'minutes_per_session.min' => 'Session duration must be at least 15 minutes.',
            'minutes_per_session.max' => 'Session duration cannot exceed 180 minutes.',
        ]);

        $user = $request->user();

        $data = new FitnessProfileData(
            primaryGoal: FitnessGoal::from($validated['primary_goal']),
            availableDaysPerWeek: $validated['available_days_per_week'],
            minutesPerSession: $validated['minutes_per_session'],
            goalDetails: $validated['goal_details'] ?? null,
        );

        $profile = $this->fitnessProfileService->updateOrCreate($user, $data);

        return Response::text(json_encode([
            'success' => true,
            'profile' => [
                'id' => $profile->id,
                'primary_goal' => $profile->primary_goal->value,
                'primary_goal_label' => $profile->primary_goal->label(),
                'goal_details' => $profile->goal_details,
                'available_days_per_week' => $profile->available_days_per_week,
                'minutes_per_session' => $profile->minutes_per_session,
            ],
            'message' => 'Fitness profile updated successfully',
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
            'primary_goal' => $schema->string()->description('Primary fitness goal: weight_loss, muscle_gain, endurance, or general_fitness'),
            'goal_details' => $schema->string()->description('Optional detailed description of specific goals (e.g., "Run a sub-4hr marathon by October")')->nullable(),
            'available_days_per_week' => $schema->integer()->description('Number of days available for training per week (1-7)'),
            'minutes_per_session' => $schema->integer()->description('Typical workout session duration in minutes (15-180)'),
        ];
    }
}
