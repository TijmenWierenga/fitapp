<?php

namespace App\Mcp\Resources;

use App\Models\Workout;
use App\Support\Markdown\MarkdownBuilder;
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

        $content = MarkdownBuilder::make()
            ->heading("Workout Schedule for {$user->name}")
            ->heading('Upcoming Workouts', 2)
            ->when($upcomingWorkouts->isEmpty(), fn (MarkdownBuilder $md) => $md
                ->line('No upcoming workouts scheduled.')
                ->blankLine())
            ->when($upcomingWorkouts->isNotEmpty(), fn (MarkdownBuilder $md) => $md
                ->each($upcomingWorkouts, function (Workout $workout, MarkdownBuilder $md) use ($user): void {
                    $scheduledAt = $user->toUserTimezone($workout->scheduled_at)->format('Y-m-d H:i');
                    $sections = $workout->sections_count > 0 ? " [{$workout->sections_count} sections]" : '';
                    $md->listItem("**{$workout->name}** ({$workout->activity->label()}){$sections}")
                        ->listItem("Scheduled: {$scheduledAt}", 1);
                    if ($workout->notes) {
                        $md->listItem("Notes: {$workout->notes}", 1);
                    }
                    $md->blankLine();
                }))
            ->heading('Recently Completed Workouts', 2)
            ->when($completedWorkouts->isEmpty(), fn (MarkdownBuilder $md) => $md
                ->line('No completed workouts yet.'))
            ->when($completedWorkouts->isNotEmpty(), fn (MarkdownBuilder $md) => $md
                ->each($completedWorkouts, function (Workout $workout, MarkdownBuilder $md) use ($user): void {
                    $completedAt = $user->toUserTimezone($workout->completed_at)->format('Y-m-d H:i');
                    $md->listItem("**{$workout->name}** ({$workout->activity->label()})")
                        ->listItem("Completed: {$completedAt}", 1)
                        ->listItem("RPE: {$workout->rpe}/10, Feeling: {$workout->feeling}/5", 1)
                        ->blankLine();
                }))
            ->toString();

        return Response::text($content);
    }
}
