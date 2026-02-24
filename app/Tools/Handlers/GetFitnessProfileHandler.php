<?php

declare(strict_types=1);

namespace App\Tools\Handlers;

use App\Models\User;
use App\Tools\ToolResult;

class GetFitnessProfileHandler
{
    public function execute(User $user): ToolResult
    {
        $profile = $user->fitnessProfile;

        if (! $profile) {
            return ToolResult::success([
                'message' => 'No fitness profile configured yet. Ask the user about their goals and preferences.',
            ]);
        }

        return ToolResult::success([
            'primary_goal' => $profile->primary_goal->value,
            'primary_goal_label' => $profile->primary_goal->label(),
            'goal_details' => $profile->goal_details,
            'available_days_per_week' => $profile->available_days_per_week,
            'minutes_per_session' => $profile->minutes_per_session,
            'prefer_garmin_exercises' => $profile->prefer_garmin_exercises,
        ]);
    }
}
