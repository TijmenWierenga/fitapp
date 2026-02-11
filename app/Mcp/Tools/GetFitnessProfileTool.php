<?php

namespace App\Mcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class GetFitnessProfileTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Get the authenticated user's fitness profile including primary goal, goal details, available training days, and session duration preferences.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $user = $request->user();
        $user->load('fitnessProfile');

        if (! $user->fitnessProfile) {
            return Response::text(<<<'TEXT'
            # Fitness Profile

            *No fitness profile configured yet.*

            Use the `update-fitness-profile` tool to set fitness goals and training preferences.

            TEXT);
        }

        $profile = $user->fitnessProfile;
        $goalDetails = $profile->goal_details ? "\n**Goal Details:** {$profile->goal_details}" : '';

        $content = <<<TEXT
        # Fitness Profile

        **Primary Goal:** {$profile->primary_goal->label()}$goalDetails
        **Available Days Per Week:** {$profile->available_days_per_week}
        **Minutes Per Session:** {$profile->minutes_per_session}

        TEXT;

        return Response::text($content);
    }
}
