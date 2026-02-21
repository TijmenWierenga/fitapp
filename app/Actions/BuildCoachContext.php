<?php

namespace App\Actions;

use App\Enums\Workout\Activity;
use App\Models\User;
use Carbon\CarbonImmutable;

class BuildCoachContext
{
    public function __construct(
        private CalculateWorkload $calculateWorkload,
    ) {}

    public function execute(User $user, ?Activity $activity = null, ?CarbonImmutable $date = null): string
    {
        $date ??= $user->currentTimeInTimezone();

        $sections = [];

        $sections[] = $this->buildProfileSection($user);
        $sections[] = $this->buildWorkloadSection($user);
        $sections[] = $this->buildInjurySection($user);
        $sections[] = $this->buildScheduleSection($user, $date);

        if ($activity !== null || $date !== null) {
            $sections[] = $this->buildInstructions($activity, $date);
        }

        return implode("\n\n", array_filter($sections));
    }

    public function buildProfileSection(User $user): string
    {
        $profile = $user->fitnessProfile;

        if (! $profile) {
            return "## Fitness Profile\n\n*No fitness profile set up.* Ask the user about their goals and preferences.";
        }

        $content = "## Fitness Profile\n\n";
        $content .= "- **Goal:** {$profile->primary_goal->label()}\n";

        if ($profile->goal_details) {
            $content .= "- **Goal details:** {$profile->goal_details}\n";
        }

        $content .= "- **Available days/week:** {$profile->available_days_per_week}\n";
        $content .= "- **Minutes per session:** {$profile->minutes_per_session}\n";
        $content .= '- **Prefer Garmin exercises:** '.($profile->prefer_garmin_exercises ? 'Yes' : 'No');

        return $content;
    }

    public function buildWorkloadSection(User $user): string
    {
        $summary = $this->calculateWorkload->execute($user);
        $content = "## Current Workload\n\n";

        if ($summary->sessionLoad === null && $summary->muscleGroupVolume->isEmpty()) {
            return $content.'*No workload data available.* This may be the user\'s first workout.';
        }

        $warnings = $summary->warnings();
        foreach ($warnings as $warning) {
            $content .= "> **Warning:** {$warning}\n\n";
        }

        if ($summary->sessionLoad !== null) {
            $load = $summary->sessionLoad;
            $content .= "### Session Load\n";
            $content .= "- Weekly total (sRPE): {$load->currentWeeklyTotal}\n";
            $content .= "- Sessions this week: {$load->currentSessionCount}\n";
            $content .= "- Monotony: {$load->monotony}\n";
            $content .= "- Week-over-week change: {$load->weekOverWeekChangePct}%\n\n";
        }

        if ($summary->muscleGroupVolume->isNotEmpty()) {
            $content .= "### Muscle Group Volume\n";
            $content .= "| Muscle Group | Current Sets | 4-Week Avg | Trend |\n|---|---|---|---|\n";
            foreach ($summary->muscleGroupVolume as $volume) {
                $content .= "| {$volume->label} | {$volume->currentWeekSets} | {$volume->fourWeekAverageSets} | {$volume->trend->value} |\n";
            }
        }

        return $content;
    }

    public function buildInjurySection(User $user): string
    {
        $injuries = $user->injuries()->active()->get();

        if ($injuries->isEmpty()) {
            return '';
        }

        $content = "## Active Injuries\n\n";
        foreach ($injuries as $injury) {
            $content .= "- **{$injury->body_part->value}** ({$injury->injury_type->value}) — since {$injury->started_at->toDateString()}";

            if ($injury->notes) {
                $content .= " — {$injury->notes}";
            }

            $content .= "\n";
        }

        return $content;
    }

    public function buildScheduleSection(User $user, CarbonImmutable $date): string
    {
        $content = "## Schedule Context\n\n";

        $upcoming = $user->workouts()
            ->whereNull('completed_at')
            ->where('scheduled_at', '>=', $date->startOfDay())
            ->where('scheduled_at', '<=', $date->addDays(7)->endOfDay())
            ->orderBy('scheduled_at')
            ->get();

        if ($upcoming->isNotEmpty()) {
            $content .= "### Upcoming (next 7 days)\n";
            foreach ($upcoming as $workout) {
                $scheduledAt = $user->toUserTimezone(CarbonImmutable::instance($workout->scheduled_at));
                $content .= "- {$scheduledAt->format('D M j')}: {$workout->name} ({$workout->activity->label()})\n";
            }
            $content .= "\n";
        }

        $recent = $user->workouts()
            ->completed()
            ->where('completed_at', '>=', $date->subDays(7)->startOfDay())
            ->limit(10)
            ->get();

        if ($recent->isNotEmpty()) {
            $content .= "### Recently Completed (last 7 days)\n";
            foreach ($recent as $workout) {
                $completedAt = $user->toUserTimezone(CarbonImmutable::instance($workout->completed_at));
                $rpeLabel = $workout->rpe ? "RPE {$workout->rpe}" : 'no RPE';
                $content .= "- {$completedAt->format('D M j')}: {$workout->name} ({$workout->activity->label()}) — {$rpeLabel}\n";
            }
        }

        if ($upcoming->isEmpty() && $recent->isEmpty()) {
            $content .= '*No upcoming or recently completed workouts.*';
        }

        return $content;
    }

    private function buildInstructions(?Activity $activity, CarbonImmutable $date): string
    {
        $content = "## Instructions\n\n";

        if ($activity) {
            $content .= "The user wants a **{$activity->label()}** workout for **{$date->format('Y-m-d')}**.\n\n";
        } else {
            $content .= "The user hasn't specified an activity. Based on the profile, workload, injuries, and schedule above, **recommend the best workout type** for **{$date->format('Y-m-d')}** and explain your reasoning.\n\n";
        }

        $content .= "Steps:\n";
        $content .= "1. Review the workload data — avoid overloading muscle groups in caution/danger zones\n";
        $content .= "2. Consider active injuries and modify exercise selection accordingly\n";
        $content .= "3. Use `search-exercises` to find appropriate exercises, linking them via `exercise_id`\n";
        $content .= "4. Use `create-workout` to build the workout with warm-up, main work, and cool-down sections\n";
        $content .= "5. Keep the workout within the user's preferred session duration";

        return $content;
    }
}
