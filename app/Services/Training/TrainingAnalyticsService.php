<?php

namespace App\Services\Training;

use App\Models\User;
use App\Models\Workout;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

class TrainingAnalyticsService
{
    public function getAnalytics(User $user, int $weeks): array
    {
        $startDate = now()->subWeeks($weeks)->startOfDay();

        $completed = $user->workouts()
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', $startDate)
            ->get();

        $overdue = $user->workouts()
            ->whereNull('completed_at')
            ->where('scheduled_at', '<', now())
            ->where('scheduled_at', '>=', $startDate)
            ->count();

        $totalCompleted = $completed->count();
        $completionRate = ($totalCompleted + $overdue) > 0
            ? round($totalCompleted / ($totalCompleted + $overdue) * 100, 1)
            : 0;

        $workoutsPerWeek = $this->calculateWorkoutsPerWeek($completed, $weeks);
        $averageRpe = $totalCompleted > 0 ? round($completed->avg('rpe'), 1) : null;
        $averageFeeling = $totalCompleted > 0 ? round($completed->avg('feeling'), 1) : null;

        $activityDistribution = $completed
            ->groupBy(fn (Workout $w) => $w->activity->value)
            ->map(fn ($group) => $group->count())
            ->sortDesc()
            ->toArray();

        $streak = $this->calculateStreak($user);

        return [
            'period_weeks' => $weeks,
            'period_start' => $startDate->toDateString(),
            'total_completed' => $totalCompleted,
            'completion_rate' => $completionRate,
            'average_rpe' => $averageRpe,
            'average_feeling' => $averageFeeling,
            'workouts_per_week' => $workoutsPerWeek,
            'activity_distribution' => $activityDistribution,
            'current_streak_days' => $streak,
        ];
    }

    protected function calculateWorkoutsPerWeek(Collection $completed, int $weeks): array
    {
        $workoutsPerWeek = [];
        for ($i = 0; $i < $weeks; $i++) {
            $weekStart = now()->subWeeks($weeks - 1 - $i)->startOfWeek();
            $weekEnd = $weekStart->endOfWeek();
            $count = $completed->filter(fn (Workout $w) => $w->completed_at->between($weekStart, $weekEnd))->count();
            $workoutsPerWeek[] = [
                'week_start' => $weekStart->toDateString(),
                'count' => $count,
            ];
        }

        return $workoutsPerWeek;
    }

    /**
     * Calculate the user's current workout streak (consecutive days with completed workouts).
     *
     * Fetches all completed workouts once and processes them in memory to avoid N+1 queries.
     * For a 365-day streak, this reduces queries from 365 to 1.
     */
    protected function calculateStreak(User $user): int
    {
        // Fetch all completed workout dates in a single query
        $completedDates = $user->workouts()
            ->whereNotNull('completed_at')
            ->orderBy('completed_at', 'desc')
            ->pluck('completed_at')
            ->map(fn ($date) => $date->toDateString())
            ->unique()
            ->values();

        if ($completedDates->isEmpty()) {
            return 0;
        }

        $streak = 0;
        $expectedDate = CarbonImmutable::today();

        foreach ($completedDates as $completedDateString) {
            $completedDate = CarbonImmutable::parse($completedDateString);

            if ($completedDate->equalTo($expectedDate)) {
                $streak++;
                $expectedDate = $expectedDate->subDay();
            } else {
                // Streak is broken
                break;
            }
        }

        return $streak;
    }
}
