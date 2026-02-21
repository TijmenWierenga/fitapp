<?php

namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class GetFitnessProfileTool implements Tool
{
    public function description(): string
    {
        return 'Get the user\'s fitness profile including primary goal, goal details, available training days, and session duration preferences.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function handle(Request $request): string
    {
        $user = auth()->user();
        $profile = $user->fitnessProfile;

        if (! $profile) {
            return json_encode(['message' => 'No fitness profile configured yet. Ask the user about their goals and preferences.']);
        }

        return json_encode([
            'primary_goal' => $profile->primary_goal->value,
            'primary_goal_label' => $profile->primary_goal->label(),
            'goal_details' => $profile->goal_details,
            'available_days_per_week' => $profile->available_days_per_week,
            'minutes_per_session' => $profile->minutes_per_session,
            'prefer_garmin_exercises' => $profile->prefer_garmin_exercises,
        ]);
    }
}
