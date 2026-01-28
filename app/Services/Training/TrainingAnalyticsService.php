<?php

namespace App\Services\Training;

use App\Models\User;
use App\Models\Workout;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

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
            $weekEnd = $weekStart->copy()->endOfWeek();
            $count = $completed->filter(fn (Workout $w) => $w->completed_at->between($weekStart, $weekEnd))->count();
            $workoutsPerWeek[] = [
                'week_start' => $weekStart->toDateString(),
                'count' => $count,
            ];
        }

        return $workoutsPerWeek;
    }

    protected function calculateStreak(User $user): int
    {
        $streak = 0;
        $date = Carbon::today();

        while (true) {
            $hasWorkout = $user->workouts()
                ->whereNotNull('completed_at')
                ->whereDate('completed_at', $date)
                ->exists();

            if (! $hasWorkout) {
                break;
            }

            $streak++;
            $date = $date->subDay();
        }

        return $streak;
    }
}
