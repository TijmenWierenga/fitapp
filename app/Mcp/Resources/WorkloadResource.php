<?php

namespace App\Mcp\Resources;

use App\Actions\CalculateWorkload;
use App\DataTransferObjects\Workload\MuscleGroupWorkload;
use App\DataTransferObjects\Workload\WorkloadSummary;
use App\Enums\WorkloadZone;
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
        Current muscle group workload summary with ACWR (Acute:Chronic Workload Ratio) zones based on the last 28 days of completed workouts.

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

        if ($summary->muscleGroups->isEmpty()) {
            $content .= "*No workload data available.* Complete workouts with linked exercises to start tracking.\n\n";

            return $this->appendInjuries($content, $summary);
        }

        if ($summary->dataSpanDays < 28) {
            $content .= "> **Data reliability:** Based on {$summary->dataSpanDays} of 28 days. ACWR values may not be reliable yet.\n\n";
        }

        $content .= "| Muscle Group | Acute Load | Chronic Load | ACWR | Zone |\n";
        $content .= "|---|---|---|---|---|\n";

        foreach ($summary->muscleGroups as $workload) {
            $content .= "| {$workload->muscleGroupLabel} | {$this->formatLoad($workload->acuteLoad)} | {$this->formatLoad($workload->chronicLoad)} | {$workload->acwr} | {$this->formatZone($workload->zone)} |\n";
        }

        $content .= "\n";

        $cautionGroups = $summary->muscleGroups->filter(fn (MuscleGroupWorkload $w): bool => $w->zone === WorkloadZone::Caution);
        $dangerGroups = $summary->muscleGroups->filter(fn (MuscleGroupWorkload $w): bool => $w->zone === WorkloadZone::Danger);

        if ($cautionGroups->isNotEmpty() || $dangerGroups->isNotEmpty()) {
            $content .= "## Warnings\n\n";

            foreach ($dangerGroups as $workload) {
                $content .= "- **DANGER:** {$workload->muscleGroupLabel} (ACWR {$workload->acwr}) — strongly recommend reducing load\n";
            }

            foreach ($cautionGroups as $workload) {
                $content .= "- **CAUTION:** {$workload->muscleGroupLabel} (ACWR {$workload->acwr}) — consider reducing load\n";
            }

            $content .= "\n";
        }

        if ($summary->unlinkedExerciseCount > 0) {
            $content .= "*Note: {$summary->unlinkedExerciseCount} exercise(s) in recent workouts are not linked to the exercise library and are excluded from workload tracking.*\n\n";
        }

        return $this->appendInjuries($content, $summary);
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

    protected function formatLoad(float $load): string
    {
        return number_format($load, 1);
    }

    protected function formatZone(WorkloadZone $zone): string
    {
        $emoji = match ($zone) {
            WorkloadZone::Danger => '🔴',
            WorkloadZone::Caution => '🟡',
            WorkloadZone::SweetSpot => '🟢',
            WorkloadZone::Undertraining => '⚪',
            WorkloadZone::Inactive => '⚫',
        };

        return "{$emoji} {$zone->label()}";
    }
}
