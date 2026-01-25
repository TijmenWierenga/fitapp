<?php

namespace App\Mcp\Resources;

use App\Models\Injury;
use App\Models\User;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Contracts\HasUriTemplate;
use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Support\UriTemplate;

class UserProfileResource extends Resource implements HasUriTemplate
{
    /**
     * The resource's description.
     */
    protected string $description = <<<'MARKDOWN'
        Read-only user profile information including name, email, timezone, fitness profile, and injuries.

        Use URI template: user://profile/{userId}
    MARKDOWN;

    /**
     * Get the URI template for this resource.
     */
    public function uriTemplate(): UriTemplate
    {
        return new UriTemplate('user://profile/{userId}');
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

        $user = User::with(['fitnessProfile', 'injuries'])->find($userId);

        if (! $user) {
            return Response::error('User not found');
        }

        $content = $this->buildProfileContent($user);

        return Response::text($content);
    }

    protected function buildProfileContent(User $user): string
    {
        $content = <<<TEXT
        # User Profile

        **Name:** {$user->name}
        **Email:** {$user->email}
        **Timezone:** {$user->timezone}
        **Initials:** {$user->initials()}

        TEXT;

        $content .= $this->buildFitnessProfileSection($user);
        $content .= $this->buildInjuriesSection($user);

        return $content;
    }

    protected function buildFitnessProfileSection(User $user): string
    {
        if (! $user->fitnessProfile) {
            return <<<'TEXT'

            ## Fitness Profile

            *No fitness profile configured yet.*

            TEXT;
        }

        $profile = $user->fitnessProfile;
        $goalDetails = $profile->goal_details ? "\n**Goal Details:** {$profile->goal_details}" : '';

        return <<<TEXT

        ## Fitness Profile

        **Primary Goal:** {$profile->primary_goal->label()}$goalDetails
        **Available Days Per Week:** {$profile->available_days_per_week}
        **Minutes Per Session:** {$profile->minutes_per_session}

        TEXT;
    }

    protected function buildInjuriesSection(User $user): string
    {
        $activeInjuries = $user->injuries->filter(fn (Injury $injury) => $injury->is_active);
        $resolvedInjuries = $user->injuries->filter(fn (Injury $injury) => ! $injury->is_active);

        $content = "\n## Injuries & Limitations\n\n";

        if ($activeInjuries->isEmpty() && $resolvedInjuries->isEmpty()) {
            return $content."*No injuries recorded.*\n";
        }

        if ($activeInjuries->isNotEmpty()) {
            $content .= "### Active Injuries\n\n";
            foreach ($activeInjuries as $injury) {
                $content .= $this->formatInjury($injury);
            }
        }

        if ($resolvedInjuries->isNotEmpty()) {
            $content .= "### Past Injuries\n\n";
            foreach ($resolvedInjuries as $injury) {
                $content .= $this->formatInjury($injury);
            }
        }

        return $content;
    }

    protected function formatInjury(Injury $injury): string
    {
        $status = $injury->is_active ? 'Active' : 'Resolved';
        $endDate = $injury->ended_at ? " - {$injury->ended_at->toDateString()}" : ' - Present';
        $notes = $injury->notes ? " ({$injury->notes})" : '';

        return "- **{$injury->body_part->label()}** [{$injury->injury_type->label()}]: {$injury->started_at->toDateString()}{$endDate}{$notes}\n";
    }
}
