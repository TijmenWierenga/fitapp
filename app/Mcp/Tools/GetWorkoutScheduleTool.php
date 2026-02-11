<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class GetWorkoutScheduleTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Get the authenticated user's workout schedule showing upcoming and recently completed workouts.
    MARKDOWN;

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'upcoming_limit' => $schema->integer()->description('Number of upcoming workouts to return (default: 20, max: 50)'),
            'completed_limit' => $schema->integer()->description('Number of completed workouts to return (default: 10, max: 50)'),
        ];
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'upcoming_limit' => 'sometimes|integer|min:1|max:50',
            'completed_limit' => 'sometimes|integer|min:1|max:50',
        ]);

        $user = $request->user();
        $upcomingLimit = $validated['upcoming_limit'] ?? 20;
        $completedLimit = $validated['completed_limit'] ?? 10;

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
