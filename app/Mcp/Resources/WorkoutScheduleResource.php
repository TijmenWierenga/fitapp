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
        - `upcoming_limit`: Number of upcoming workouts to return (default: 20, max: 50)
        - `completed_limit`: Number of completed workouts to return (default: 10, max: 50)
    MARKDOWN;

    /**
     * Handle the resource request.
     */
    public function handle(Request $request): Response
    {
        $user = $request->user();

        $upcomingLimit = min((int) ($request->get('upcoming_limit') ?? 20), 50);
        $completedLimit = min((int) ($request->get('completed_limit') ?? 10), 50);

        $upcomingWorkouts = $user->workouts()->upcoming()->withCount('sections')->limit($upcomingLimit)->get();
        $completedWorkouts = $user->workouts()->completed()->withCount('sections')->limit($completedLimit)->get();

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
