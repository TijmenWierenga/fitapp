<?php

declare(strict_types=1);

namespace App\Services\FitnessProfile;

use App\Data\FitnessProfileData;
use App\Models\FitnessProfile;
use App\Models\User;

class FitnessProfileService
{
    public function updateOrCreate(User $user, FitnessProfileData $data): FitnessProfile
    {
        return $user->fitnessProfile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'primary_goal' => $data->primaryGoal,
                'goal_details' => $data->goalDetails,
                'available_days_per_week' => $data->availableDaysPerWeek,
                'minutes_per_session' => $data->minutesPerSession,
            ]
        );
    }

    public function get(User $user): ?FitnessProfile
    {
        return $user->fitnessProfile;
    }
}
