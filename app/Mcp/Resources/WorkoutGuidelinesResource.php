<?php

namespace App\Mcp\Resources;

use Laravel\Mcp\Enums\Role;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Annotations\Audience;
use Laravel\Mcp\Server\Annotations\Priority;
use Laravel\Mcp\Server\Resource;

#[Audience(Role::Assistant)]
#[Priority(0.5)]
class WorkoutGuidelinesResource extends Resource
{
    /**
     * The resource URI.
     */
    protected string $uri = 'workout://guidelines';

    /**
     * The resource's description.
     */
    protected string $description = <<<'MARKDOWN'
        Workout structure requirements, activity-specific conventions, Garmin FIT compatibility details, and pain score scale.

        Read this resource when creating or completing workouts.
    MARKDOWN;

    /**
     * Handle the resource request.
     */
    public function handle(Request $request): Response
    {
        return Response::text($this->buildContent());
    }

    protected function buildContent(): string
    {
        return <<<'MARKDOWN'
            # Workout Guidelines

            ## Workout Structure

            Every structured workout MUST include three sections in this order:

            1. **Warm-Up** — Prepare the body for the main work. Include light cardio, dynamic stretches, or activation exercises relevant to the workout. Typically 5–10 minutes.
            2. **Main Work** — The core training block(s) with the primary exercises.
            3. **Cool-Down** — Aid recovery with static stretching, foam rolling, or light movement targeting the muscles worked. Typically 5–10 minutes.

            ### Activity-Specific Conventions

            - **Strength:** Warm-up with light sets or mobility drills for the target muscles; cool-down with static stretches for worked muscle groups
            - **Running/Cardio:** Warm-up with easy pace or dynamic leg stretches; cool-down with walking and lower body stretches
            - **Yoga/Mobility:** Warm-up can be gentler; cool-down may include savasana or breathing exercises

            ## Garmin FIT Compatibility

            Some exercises in the catalog have Garmin FIT exercise mappings (`garmin_compatible: true` in search results). When these exercises are used in workouts, the FIT export includes Garmin exercise category and name IDs, enabling Garmin devices to display exercise animations and properly track exercises.

            - Check the user's `prefer_garmin_exercises` setting in their fitness profile (available via `user://fitness-profile` resource)
            - When enabled, use `garmin_compatible: true` filter in `search-exercises` to prefer mapped exercises
            - Unmapped exercises still export fine — they just won't show Garmin animations on the device

            ## Pain Score Scale (NRS 0–10)

            When completing a workout, if the user has active injuries, collect pain scores for each relevant injury:

            | Score | Severity |
            |---|---|
            | 0 | No Pain |
            | 1–3 | Mild |
            | 4–6 | Moderate |
            | 7–10 | Severe |

            Pain scores are optional but encouraged for users with active injuries to enable pain trend tracking over time. Only active (not resolved) injuries can receive pain scores.
            MARKDOWN;
    }
}
