<?php

namespace App\Mcp\Resources;

use App\Models\User;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Resource;

class UserFitnessProfileResource extends Resource
{
    /**
     * The resource URI.
     */
    protected string $uri = 'user://fitness-profile';

    /**
     * The resource's description.
     */
    protected string $description = <<<'MARKDOWN'
        Read-only fitness profile including primary goal, goal details, available training days, and session duration preferences.

        Use URI: user://fitness-profile
    MARKDOWN;

    /**
     * Handle the resource request.
     */
    public function handle(Request $request): Response
    {
        $user = $request->user();
        $user->load('fitnessProfile');

        $content = $this->buildFitnessProfileContent($user);

        return Response::text($content);
    }

    protected function buildFitnessProfileContent(User $user): string
    {
        if (! $user->fitnessProfile) {
            return <<<'TEXT'
            # Fitness Profile

            *No fitness profile configured yet.*

            Use the `update-fitness-profile` tool to set fitness goals and training preferences.

            TEXT;
        }

        $profile = $user->fitnessProfile;
        $goalDetails = $profile->goal_details ? "\n**Goal Details:** {$profile->goal_details}" : '';

        return <<<TEXT
        # Fitness Profile

        **Primary Goal:** {$profile->primary_goal->label()}$goalDetails
        **Available Days Per Week:** {$profile->available_days_per_week}
        **Minutes Per Session:** {$profile->minutes_per_session}

        TEXT;
    }
}
