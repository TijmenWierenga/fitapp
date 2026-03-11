<?php

namespace App\Mcp\Resources;

use App\Models\FitnessProfile;
use App\Models\User;
use App\Support\Markdown\MarkdownBuilder;
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

        $equipment = $profile->home_equipment
            ? collect($profile->home_equipment)
                ->map(fn (string $value): string => \App\Enums\Equipment::from($value)->label())
                ->implode(', ')
            : null;

        return MarkdownBuilder::make()
            ->heading('Fitness Profile')
            ->field('Primary Goal', $profile->primary_goal->label())
            ->field('Goal Details', $profile->goal_details)
            ->field('Available Days Per Week', $profile->available_days_per_week)
            ->field('Minutes Per Session', $profile->minutes_per_session)
            ->field('Prefer Garmin-Compatible Exercises', $profile->prefer_garmin_exercises)
            ->field('Experience Level', $profile->experience_level?->label())
            ->field('Age', $profile->age)
            ->field('Biological Sex', $profile->biological_sex?->label())
            ->field('Body Weight', $profile->body_weight_kg, 'kg')
            ->field('Height', $profile->height_cm, 'cm')
            ->field('Gym Access', $this->formatGymAccess($profile))
            ->field('Home Equipment', $equipment)
            ->toString();
    }

    private function formatGymAccess(FitnessProfile $profile): string
    {
        return $profile->has_gym_access
            ? 'Yes (standard gym equipment available)'
            : 'No';
    }
}
