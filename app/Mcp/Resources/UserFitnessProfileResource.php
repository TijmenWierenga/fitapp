<?php

namespace App\Mcp\Resources;

use App\Models\FitnessProfile;
use App\Models\User;
use Laravel\Mcp\Enums\Role;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Annotations\Audience;
use Laravel\Mcp\Server\Annotations\Priority;
use Laravel\Mcp\Server\Resource;

#[Audience(Role::Assistant)]
#[Priority(0.8)]
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
        Read-only fitness profile including primary goal, goal details, available training days, session duration preferences, physical attributes, and equipment availability.

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

        return "# Fitness Profile\n\n"
            .$this->buildGoalSection($profile)
            .$this->buildPhysicalSection($profile)
            .$this->buildEquipmentSection($profile);
    }

    private function buildGoalSection(FitnessProfile $profile): string
    {
        $content = "**Primary Goal:** {$profile->primary_goal->label()}\n";

        if ($profile->goal_details) {
            $content .= "**Goal Details:** {$profile->goal_details}\n";
        }

        $content .= "**Available Days Per Week:** {$profile->available_days_per_week}\n";
        $content .= "**Minutes Per Session:** {$profile->minutes_per_session}\n";
        $content .= '**Prefer Garmin-Compatible Exercises:** '.($profile->prefer_garmin_exercises ? 'Yes' : 'No')."\n";

        return $content;
    }

    private function buildPhysicalSection(FitnessProfile $profile): string
    {
        $content = '';

        if ($profile->experience_level) {
            $content .= "**Experience Level:** {$profile->experience_level->label()}\n";
        }

        if ($profile->age !== null) {
            $content .= "**Age:** {$profile->age}\n";
        }

        if ($profile->biological_sex) {
            $content .= "**Biological Sex:** {$profile->biological_sex->label()}\n";
        }

        if ($profile->body_weight_kg !== null) {
            $content .= "**Body Weight:** {$profile->body_weight_kg} kg\n";
        }

        if ($profile->height_cm !== null) {
            $content .= "**Height:** {$profile->height_cm} cm\n";
        }

        return $content;
    }

    private function buildEquipmentSection(FitnessProfile $profile): string
    {
        $content = '';

        if ($profile->has_gym_access) {
            $content .= "**Gym Access:** Yes (standard gym equipment available)\n";
        } else {
            $content .= "**Gym Access:** No\n";
        }

        if ($profile->home_equipment) {
            $equipment = collect($profile->home_equipment)
                ->map(fn (string $value): string => \App\Enums\Equipment::from($value)->label())
                ->implode(', ');
            $content .= "**Home Equipment:** {$equipment}\n";
        }

        return $content;
    }
}
