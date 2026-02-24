<?php

namespace App\Mcp\Resources;

use Laravel\Mcp\Enums\Role;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Annotations\Audience;
use Laravel\Mcp\Server\Annotations\Priority;
use Laravel\Mcp\Server\Resource;

#[Audience(Role::Assistant)]
#[Priority(0.9)]
class WorkoutScheduleResource extends Resource
{
    /**
     * The resource URI.
     */
    protected string $uri = 'workout://schedule';

    /**
     * The resource's description.
     */
    protected string $description = <<<'MARKDOWN'
        Read-only workout schedule showing upcoming and recently completed workouts.

        Use URI: workout://schedule

        Optional parameters:
        - `upcoming_days`: Number of days ahead to look for upcoming workouts (default: 14, max: 90)
        - `completed_days`: Number of days back to look for completed workouts (default: 7, max: 90)
    MARKDOWN;

    /**
     * Handle the resource request.
     */
    public function handle(Request $request): Response
    {
        $user = $request->user();

        $upcomingDays = min((int) ($request->get('upcoming_days') ?? 14), 90);
        $completedDays = min((int) ($request->get('completed_days') ?? 7), 90);

        $upcomingWorkouts = $user->workouts()->upcoming()
            ->where('scheduled_at', '<=', now()->addDays($upcomingDays))
            ->withCount('sections')
            ->limit(50)
            ->get();

        $completedWorkouts = $user->workouts()->completed()
            ->where('completed_at', '>=', now()->subDays($completedDays))
            ->withCount('sections')
            ->limit(50)
            ->get();

        $content = "# Workout Schedule for {$user->name}\n\n";

        $content .= "## Upcoming Workouts\n\n";
        if ($upcomingWorkouts->isEmpty()) {
            $content .= "No upcoming workouts scheduled.\n\n";
        } else {
            foreach ($upcomingWorkouts as $workout) {
                $scheduledAt = $user->toUserTimezone($workout->scheduled_at)->format('Y-m-d H:i');
                $content .= "- **{$workout->name}** ({$workout->activity->label()})";
                if ($workout->sections_count > 0) {
                    $content .= " [{$workout->sections_count} sections]";
                }
                $content .= "\n";
                $content .= "  Scheduled: {$scheduledAt}\n";
                if ($workout->notes) {
                    $content .= "  Notes: {$workout->notes}\n";
                }
                $content .= "\n";
            }
        }

        $content .= "## Recently Completed Workouts\n\n";
        if ($completedWorkouts->isEmpty()) {
            $content .= "No completed workouts yet.\n";
        } else {
            foreach ($completedWorkouts as $workout) {
                $completedAt = $user->toUserTimezone($workout->completed_at)->format('Y-m-d H:i');
                $content .= "- **{$workout->name}** ({$workout->activity->label()})\n";
                $content .= "  Completed: {$completedAt}\n";
                $content .= "  RPE: {$workout->rpe}/10, Feeling: {$workout->feeling}/5\n\n";
            }
        }

        return Response::text($content);
    }
}
