<?php

namespace App\Ai\Tools;

use App\Enums\FitnessGoal;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class UpdateFitnessProfileTool implements Tool
{
    public function description(): string
    {
        return <<<'TEXT'
        Update or create the user's fitness profile. Set their fitness goals, available training days, and session duration preferences.

        Primary Goals: weight_loss, muscle_gain, endurance, general_fitness.
        TEXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'primary_goal' => $schema->string()->enum(FitnessGoal::class)->description('Primary fitness goal.'),
            'goal_details' => $schema->string()->description('Optional detailed description of specific goals')->nullable(),
            'available_days_per_week' => $schema->integer()->description('Number of days available for training per week (1-7)'),
            'minutes_per_session' => $schema->integer()->description('Typical workout session duration in minutes (15-180)'),
            'prefer_garmin_exercises' => $schema->boolean()->description('When true, prefer exercises with Garmin FIT mapping.')->nullable(),
        ];
    }

    public function handle(Request $request): string
    {
        $user = auth()->user();

        $profile = $user->fitnessProfile()->updateOrCreate(
            ['user_id' => $user->getKey()],
            [
                'primary_goal' => FitnessGoal::from($request['primary_goal']),
                'goal_details' => $request['goal_details'] ?? null,
                'available_days_per_week' => $request['available_days_per_week'],
                'minutes_per_session' => $request['minutes_per_session'],
                ...$request->has('prefer_garmin_exercises')
                    ? ['prefer_garmin_exercises' => $request['prefer_garmin_exercises'] ?? false]
                    : [],
            ]
        );

        return json_encode([
            'success' => true,
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
