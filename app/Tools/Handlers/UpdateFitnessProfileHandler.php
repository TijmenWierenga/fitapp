<?php

declare(strict_types=1);

namespace App\Tools\Handlers;

use App\Enums\FitnessGoal;
use App\Models\User;
use App\Tools\Input\UpdateFitnessProfileInput;
use App\Tools\ToolResult;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class UpdateFitnessProfileHandler
{
    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'primary_goal' => $schema->string()->enum(FitnessGoal::class)->description('Primary fitness goal.'),
            'goal_details' => $schema->string()->description('Optional detailed description of specific goals (e.g., "Run a sub-4hr marathon by October")')->nullable(),
            'available_days_per_week' => $schema->integer()->description('Number of days available for training per week (1-7)'),
            'minutes_per_session' => $schema->integer()->description('Typical workout session duration in minutes (15-180)'),
            'prefer_garmin_exercises' => $schema->boolean()->description('When true, prefer exercises with Garmin FIT mapping for device compatibility. Use `garmin_compatible` filter in search-exercises tool.')->nullable(),
        ];
    }

    public function execute(User $user, UpdateFitnessProfileInput $input): ToolResult
    {
        $profile = $user->fitnessProfile()->updateOrCreate(
            ['user_id' => $user->getKey()],
            [
                'primary_goal' => FitnessGoal::from($input->primaryGoal),
                'goal_details' => $input->goalDetails,
                'available_days_per_week' => $input->availableDaysPerWeek,
                'minutes_per_session' => $input->minutesPerSession,
                ...$input->has('prefer_garmin_exercises')
                    ? ['prefer_garmin_exercises' => $input->preferGarminExercises ?? false]
                    : [],
            ]
        );

        return ToolResult::success([
            'profile' => [
                'id' => $profile->id,
                'primary_goal' => $profile->primary_goal->value,
                'primary_goal_label' => $profile->primary_goal->label(),
                'goal_details' => $profile->goal_details,
                'available_days_per_week' => $profile->available_days_per_week,
                'minutes_per_session' => $profile->minutes_per_session,
                'prefer_garmin_exercises' => $profile->prefer_garmin_exercises,
            ],
            'message' => 'Fitness profile updated successfully',
        ]);
    }
}
