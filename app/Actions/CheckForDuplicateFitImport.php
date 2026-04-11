<?php

declare(strict_types=1);

namespace App\Actions;

use App\Actions\Garmin\SportMapper;
use App\DataTransferObjects\Fit\ParsedActivity;
use App\Enums\Workout\WorkoutSource;
use App\Models\User;
use App\Models\Workout;

class CheckForDuplicateFitImport
{
    public function execute(User $user, ParsedActivity $parsed): ?Workout
    {
        $activity = SportMapper::toActivity($parsed->session->sport, $parsed->session->subSport);

        return Workout::query()
            ->where('user_id', $user->id)
            ->where('activity', $activity)
            ->where('source', WorkoutSource::GarminFit)
            ->whereDate('scheduled_at', $parsed->session->startTime->toDateString())
            ->first();
    }
}
