<?php

declare(strict_types=1);

namespace App\Support\Fit\Decode;

use App\DataTransferObjects\Fit\ParsedActivity;
use App\Models\User;
use App\Models\Workout;
use App\Support\Fit\SportMapper;
use Illuminate\Support\Collection;

class FindMatchingWorkout
{
    /**
     * @return Collection<int, Workout>
     */
    public function execute(User $user, ParsedActivity $activity): Collection
    {
        $activityType = SportMapper::toActivity(
            $activity->session->sport,
            $activity->session->subSport,
        );

        $activityDate = $activity->session->startTime
            ->setTimezone($user->timezone ?? 'UTC')
            ->startOfDay();

        $startTimeFormatted = $activity->session->startTime->toDateTimeString();

        return Workout::query()
            ->where('user_id', $user->id)
            ->whereNull('completed_at')
            ->where('activity', $activityType)
            ->whereDate('scheduled_at', $activityDate)
            ->orderByRaw("ABS(strftime('%s', scheduled_at) - strftime('%s', ?))", [$startTimeFormatted])
            ->get();
    }
}
