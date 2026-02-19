<?php

namespace App\Mcp\Prompts;

use App\Actions\CalculateWorkload;
use App\Models\User;
use App\Models\Workout;
use Carbon\CarbonImmutable;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

class ReviewProgressPrompt extends Prompt
{
    protected string $name = 'review-progress';

    protected string $description = 'Analyze training progress over a timeframe and recommend adjustments based on workload trends, recovery, and goal alignment.';

    public function __construct(
        private CalculateWorkload $calculateWorkload,
    ) {}

    /**
     * @return array<int, Argument>
     */
    public function arguments(): array
    {
        return [
            new Argument(
                name: 'timeframe_days',
                description: 'Review period in days. Defaults to 28.',
                required: false,
            ),
        ];
    }

    public function handle(Request $request): ResponseFactory
    {
        /** @var User $user */
        $user = $request->user();
        $timeframeDays = (int) ($request->get('timeframe_days') ?? 28);
        $timeframeDays = max(7, min(90, $timeframeDays));

        $now = $user->currentTimeInTimezone();
        $context = $this->buildContext($user, $now, $timeframeDays);

        $weeks = (int) ceil($timeframeDays / 7);
        $acknowledgement = "I'll review your training progress over the last {$timeframeDays} days ({$weeks} weeks). Let me analyze your data.";

        return Response::make([
            Response::text($acknowledgement)->asAssistant(),
            Response::text($context),
        ]);
    }

    private function buildContext(User $user, CarbonImmutable $now, int $timeframeDays): string
    {
        $sections = [];

        $sections[] = $this->buildProfileSection($user);
        $sections[] = $this->buildWorkloadSection($user);
        $sections[] = $this->buildCompletedWorkoutsSection($user, $now, $timeframeDays);
        $sections[] = $this->buildInjurySection($user);
        $sections[] = $this->buildUpcomingSection($user, $now);
        $sections[] = $this->buildAnalysisFramework($timeframeDays);

        return implode("\n\n", array_filter($sections));
    }

    private function buildProfileSection(User $user): string
    {
        $profile = $user->fitnessProfile;

        if (! $profile) {
            return "## Fitness Profile\n\n*No fitness profile set up.* Cannot measure progress against goals without a profile.";
        }

        return "## Fitness Profile (goal to measure against)\n\n"
            ."- **Goal:** {$profile->primary_goal->label()}\n"
            ."- **Available days/week:** {$profile->available_days_per_week}\n"
            ."- **Minutes per session:** {$profile->minutes_per_session}";
    }

    private function buildWorkloadSection(User $user): string
    {
        $summary = $this->calculateWorkload->execute($user);
        $content = "## Workload Summary\n\n";

        if ($summary->sessionLoad === null && $summary->muscleGroupVolume->isEmpty() && empty($summary->strengthProgression)) {
            return $content.'*No workload data available.*';
        }

        $warnings = $summary->warnings();
        foreach ($warnings as $warning) {
            $content .= "> **Warning:** {$warning}\n\n";
        }

        if ($summary->sessionLoad !== null) {
            $load = $summary->sessionLoad;
            $content .= "### Session Load\n";
            $content .= "| Metric | Value |\n|---|---|\n";
            $content .= "| Weekly Total (sRPE) | {$load->currentWeeklyTotal} |\n";
            $content .= "| Sessions This Week | {$load->currentSessionCount} |\n";
            $content .= "| Monotony | {$load->monotony} |\n";
            $content .= "| Strain | {$load->strain} |\n";
            $content .= "| Week-over-Week Change | {$load->weekOverWeekChangePct}% |\n\n";

            if (! empty($load->previousWeeks)) {
                $content .= "### Previous Weeks\n";
                $content .= "| Week | Total Load | Sessions |\n|---|---|---|\n";
                foreach ($load->previousWeeks as $week) {
                    $content .= "| {$week->weekOffset} | {$week->totalLoad} | {$week->sessionCount} |\n";
                }
                $content .= "\n";
            }
        }

        if ($summary->muscleGroupVolume->isNotEmpty()) {
            $content .= "### Muscle Group Volume\n";
            $content .= "| Muscle Group | Current Sets | 4-Week Avg | Trend |\n|---|---|---|---|\n";
            foreach ($summary->muscleGroupVolume as $volume) {
                $content .= "| {$volume->label} | {$volume->currentWeekSets} | {$volume->fourWeekAverageSets} | {$volume->trend->value} |\n";
            }
            $content .= "\n";
        }

        if (! empty($summary->strengthProgression)) {
            $content .= "### Strength Progression\n";
            $content .= "| Exercise | Current e1RM | Previous e1RM | Change |\n|---|---|---|---|\n";
            foreach ($summary->strengthProgression as $progression) {
                $previous = $progression->previousE1RM !== null ? number_format($progression->previousE1RM, 1) : '-';
                $change = $progression->changePct !== null ? "{$progression->changePct}%" : '-';
                $content .= "| {$progression->exerciseName} | {$progression->currentE1RM} | {$previous} | {$change} |\n";
            }
        }

        return $content;
    }

    private function buildCompletedWorkoutsSection(User $user, CarbonImmutable $now, int $timeframeDays): string
    {
        $from = CarbonImmutable::now()->subDays($timeframeDays);

        $workouts = $user->workouts()
            ->completedBetween($from, CarbonImmutable::now())
            ->get();

        if ($workouts->isEmpty()) {
            return "## Completed Workouts\n\n*No completed workouts in the last {$timeframeDays} days.*";
        }

        $content = "## Completed Workouts ({$workouts->count()} in last {$timeframeDays} days)\n\n";

        $avgRpe = $workouts->whereNotNull('rpe')->avg('rpe');
        $avgFeeling = $workouts->whereNotNull('feeling')->avg('feeling');
        $content .= "- **Total workouts:** {$workouts->count()}\n";

        if ($avgRpe !== null) {
            $content .= '- **Avg RPE:** '.number_format($avgRpe, 1)."\n";
        }

        if ($avgFeeling !== null) {
            $content .= '- **Avg feeling:** '.number_format($avgFeeling, 1)."/5\n";
        }

        $byActivity = $workouts->groupBy(fn (Workout $w): string => $w->activity->label());
        $content .= "\n### By Activity\n";
        foreach ($byActivity as $activity => $group) {
            $content .= "- {$activity}: {$group->count()} workouts\n";
        }

        return $content;
    }

    private function buildInjurySection(User $user): string
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

    private function buildUpcomingSection(User $user, CarbonImmutable $now): string
    {
        $upcoming = $user->workouts()
            ->whereNull('completed_at')
            ->where('scheduled_at', '>=', $now->startOfDay())
            ->where('scheduled_at', '<=', $now->addDays(7)->endOfDay())
            ->orderBy('scheduled_at')
            ->get();

        if ($upcoming->isEmpty()) {
            return '';
        }

        $content = "## Upcoming Schedule (next 7 days)\n\n";
        foreach ($upcoming as $workout) {
            $scheduledAt = $user->toUserTimezone(CarbonImmutable::instance($workout->scheduled_at));
            $content .= "- {$scheduledAt->format('D M j')}: {$workout->name} ({$workout->activity->label()})\n";
        }

        return $content;
    }

    private function buildAnalysisFramework(int $timeframeDays): string
    {
        return <<<MARKDOWN
## Analysis Framework

Evaluate the user's training over the last {$timeframeDays} days using these dimensions:

1. **Volume trend** — Is weekly volume increasing, stable, or decreasing? Is the rate of change sustainable?
2. **Muscle balance** — Are any muscle groups significantly over- or under-trained compared to others?
3. **Recovery quality** — Look at RPE and feeling patterns. High RPE with low feeling may indicate insufficient recovery.
4. **Injury patterns** — Are active injuries related to training volume or specific exercises?
5. **Goal alignment** — Is the training mix (activities, intensity) aligned with the stated fitness goal?
6. **Consistency** — Is the user training the intended number of days per week?

Provide specific, actionable recommendations. If adjustments are needed, explain what to change and why.
MARKDOWN;
    }
}
