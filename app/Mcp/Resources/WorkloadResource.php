<?php

namespace App\Mcp\Resources;

use App\Actions\CalculateWorkload;
use App\DataTransferObjects\Workload\WorkloadSummary;
use Laravel\Mcp\Enums\Role;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Annotations\Audience;
use Laravel\Mcp\Server\Annotations\Priority;
use Laravel\Mcp\Server\Resource;

#[Audience(Role::Assistant)]
#[Priority(0.8)]
class WorkloadResource extends Resource
{
    /**
     * The resource URI.
     */
    protected string $uri = 'workout://workload';

    /**
     * The resource's description.
     */
    protected string $description = <<<'MARKDOWN'
        Current training workload summary with session load, muscle group volume, and strength progression based on up to 56 days of completed workouts.

        Use this to understand training load before creating new workouts.
    MARKDOWN;

    public function __construct(
        private CalculateWorkload $calculateWorkload,
    ) {}

    /**
     * Handle the resource request.
     */
    public function handle(Request $request): Response
    {
        $user = $request->user();
        $summary = $this->calculateWorkload->execute($user);

        $content = $this->buildWorkloadContent($summary);

        return Response::text($content);
    }

    protected function buildWorkloadContent(WorkloadSummary $summary): string
    {
        $content = "# Workload Summary\n\n";

        $hasData = $summary->sessionLoad !== null
            || $summary->muscleGroupVolume->isNotEmpty()
            || ! empty($summary->strengthProgression);

        if (! $hasData) {
            $content .= "*No workload data available.* Complete workouts with linked exercises to start tracking.\n\n";

            return $this->appendInjuries($content, $summary);
        }

        $warnings = $summary->warnings();
        if ($warnings->isNotEmpty()) {
            foreach ($warnings as $warning) {
                $content .= "> **Warning:** {$warning}\n\n";
            }
        }

        $content .= $this->buildSessionLoadSection($summary);
        $content .= $this->buildVolumeSection($summary);
        $content .= $this->buildProgressionSection($summary);

        return $this->appendInjuries($content, $summary);
    }

    protected function buildSessionLoadSection(WorkloadSummary $summary): string
    {
        if ($summary->sessionLoad === null) {
            return "## Session Load\n\n*No session load data.* Complete workouts with duration to enable session load tracking.\n\n";
        }

        $load = $summary->sessionLoad;
        $content = "## Session Load\n\n";
        $content .= "| Metric | Value |\n|---|---|\n";
        $content .= "| Weekly Total (sRPE) | {$load->currentWeeklyTotal} |\n";
        $content .= "| Sessions This Week | {$load->currentSessionCount} |\n";
        $content .= "| Monotony | {$load->monotony} |\n";
        $content .= "| Strain | {$load->strain} |\n";
        $content .= "| Week-over-Week Change | {$load->weekOverWeekChangePct}% |\n\n";

        if (! empty($load->previousWeeks)) {
            $content .= "### Previous Weeks\n\n";
            $content .= "| Week | Total Load | Sessions |\n|---|---|---|\n";
            foreach ($load->previousWeeks as $week) {
                $content .= "| {$week->weekOffset} | {$week->totalLoad} | {$week->sessionCount} |\n";
            }
            $content .= "\n";
        }

        return $content;
    }

    protected function buildVolumeSection(WorkloadSummary $summary): string
    {
        if ($summary->muscleGroupVolume->isEmpty()) {
            return '';
        }

        $content = "## Muscle Group Volume\n\n";
        $content .= "| Muscle Group | Current Sets | 4-Week Avg | Trend |\n|---|---|---|---|\n";

        foreach ($summary->muscleGroupVolume as $volume) {
            $trendIcon = match ($volume->trend->value) {
                'increasing' => "\u{2191}",
                'decreasing' => "\u{2193}",
                default => "\u{2192}",
            };
            $content .= "| {$volume->label} | {$volume->currentWeekSets} | {$volume->fourWeekAverageSets} | {$trendIcon} {$volume->trend->value} |\n";
        }

        $content .= "\n";

        return $content;
    }

    protected function buildProgressionSection(WorkloadSummary $summary): string
    {
        if (empty($summary->strengthProgression)) {
            return '';
        }

        $content = "## Strength Progression\n\n";
        $content .= "| Exercise | Current e1RM | Previous e1RM | Change |\n|---|---|---|---|\n";

        foreach ($summary->strengthProgression as $progression) {
            $previous = $progression->previousE1RM !== null ? number_format($progression->previousE1RM, 1) : '-';
            $change = $progression->changePct !== null ? "{$progression->changePct}%" : '-';
            $content .= "| {$progression->exerciseName} | {$progression->currentE1RM} | {$previous} | {$change} |\n";
        }

        $content .= "\n";

        return $content;
    }

    protected function appendInjuries(string $content, WorkloadSummary $summary): string
    {
        if ($summary->activeInjuries->isEmpty()) {
            return $content;
        }

        $content .= "## Active Injuries\n\n";

        foreach ($summary->activeInjuries as $injury) {
            $content .= "- **{$injury['body_part']}** ({$injury['injury_type']}) — since {$injury['started_at']}\n";
        }

        $content .= "\n";

        return $content;
    }
}
