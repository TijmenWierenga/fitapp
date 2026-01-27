<?php

namespace App\Mcp\Tools;

use App\Models\User;
use App\Models\Workout;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Carbon;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class GetTrainingAnalyticsTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Get aggregated training analytics for a user over a specified period.

        Returns total workouts completed, workouts per week, completion rate,
        average RPE and feeling, activity distribution, and current streak.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'weeks' => 'nullable|integer|min:1|max:12',
        ], [
            'user_id.exists' => 'User not found. Please provide a valid user ID.',
        ]);

        $user = User::findOrFail($validated['user_id']);
        $weeks = $validated['weeks'] ?? 4;
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

        $averageRpe = $totalCompleted > 0 ? round($completed->avg('rpe'), 1) : null;
        $averageFeeling = $totalCompleted > 0 ? round($completed->avg('feeling'), 1) : null;

        $activityDistribution = $completed
            ->groupBy(fn (Workout $w) => $w->activity->value)
            ->map(fn ($group) => $group->count())
            ->sortDesc()
            ->toArray();

        $streak = $this->calculateStreak($user);

        return Response::text(json_encode([
            'success' => true,
            'period_weeks' => $weeks,
            'period_start' => $startDate->toDateString(),
            'total_completed' => $totalCompleted,
            'completion_rate' => $completionRate,
            'average_rpe' => $averageRpe,
            'average_feeling' => $averageFeeling,
            'workouts_per_week' => $workoutsPerWeek,
            'activity_distribution' => $activityDistribution,
            'current_streak_days' => $streak,
        ]));
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

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'user_id' => $schema->integer()->description('The ID of the user'),
            'weeks' => $schema->integer()->description('Number of weeks to analyze (default: 4, max: 12)')->nullable(),
        ];
    }
}
