<?php

declare(strict_types=1);

namespace App\Actions\Garmin;

use App\DataTransferObjects\Fit\ParsedActivity;
use App\Models\User;
use App\Models\Workout;
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

        $startTime = $activity->session->startTime;

        return Workout::query()
            ->where('user_id', $user->id)
            ->whereNull('completed_at')
            ->where('activity', $activityType)
            ->whereDate('scheduled_at', $activityDate)
            ->get()
            ->sortBy(fn (Workout $w) => abs($w->scheduled_at->diffInSeconds($startTime)));
    }
}
