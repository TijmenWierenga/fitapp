<?php

namespace App\Mcp\Resources;

use App\Models\User;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Contracts\HasUriTemplate;
use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Support\UriTemplate;

class WorkoutScheduleResource extends Resource implements HasUriTemplate
{
    /**
     * The resource's description.
     */
    protected string $description = <<<'MARKDOWN'
        Read-only workout schedule showing upcoming and recently completed workouts.

        Use URI template: workout://schedule/{userId}
    MARKDOWN;

    /**
     * Get the URI template for this resource.
     */
    public function uriTemplate(): UriTemplate
    {
        return new UriTemplate('workout://schedule/{userId}');
    }

    /**
     * Handle the resource request.
     */
    public function handle(Request $request): Response
    {
        $userId = $request->get('userId');

        if (! $userId) {
            return Response::error('User ID is required');
        }

        $user = User::find($userId);

        if (! $user) {
            return Response::error('User not found');
        }

        $upcomingWorkouts = $user->workouts()->upcoming()->limit(10)->get();
        $completedWorkouts = $user->workouts()->completed()->limit(5)->get();

        $content = "# Workout Schedule for {$user->name}\n\n";

        $content .= "## Upcoming Workouts\n\n";
        if ($upcomingWorkouts->isEmpty()) {
            $content .= "No upcoming workouts scheduled.\n\n";
        } else {
            foreach ($upcomingWorkouts as $workout) {
                $scheduledAt = $user->toUserTimezone($workout->scheduled_at)->format('Y-m-d H:i');
                $content .= "- **{$workout->name}** ({$workout->sport->label()})\n";
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
                $content .= "- **{$workout->name}** ({$workout->sport->label()})\n";
                $content .= "  Completed: {$completedAt}\n";
                $content .= "  RPE: {$workout->rpe}/10, Feeling: {$workout->feeling}/5\n\n";
            }
        }

        return Response::text($content);
    }
}
