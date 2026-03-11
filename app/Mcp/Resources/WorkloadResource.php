<?php

namespace App\Mcp\Resources;

use App\Actions\CalculateWorkload;
use App\DataTransferObjects\Workload\WorkloadSummary;
use App\Support\Markdown\MarkdownBuilder;
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
        $hasData = $summary->sessionLoad !== null
            || $summary->muscleGroupVolume->isNotEmpty()
            || ! empty($summary->strengthProgression);

        if (! $hasData) {
            return MarkdownBuilder::make()
                ->heading('Workload Summary')
                ->line('*No workload data available.* Complete workouts with linked exercises to start tracking.')
                ->blankLine()
                ->when($summary->activeInjuries->isNotEmpty(), fn (MarkdownBuilder $md) => $md
                    ->heading('Active Injuries', 2)
                    ->each($summary->activeInjuries, fn (array $injury, MarkdownBuilder $md) => $md
                        ->listItem("**{$injury['body_part']}** ({$injury['injury_type']}) — since {$injury['started_at']}"))
                    ->blankLine())
                ->toString();
        }

        $warnings = $summary->warnings();

        return MarkdownBuilder::make()
            ->heading('Workload Summary')
            ->each($warnings, fn (string $warning, MarkdownBuilder $md) => $md
                ->line("> **Warning:** {$warning}")
                ->blankLine())
            ->when($summary->sessionLoad !== null, fn (MarkdownBuilder $md) => $this->buildSessionLoadSection($md, $summary))
            ->when($summary->sessionLoad === null, fn (MarkdownBuilder $md) => $md
                ->heading('Session Load', 2)
                ->line('*No session load data.* Complete workouts with duration to enable session load tracking.')
                ->blankLine())
            ->when($summary->muscleGroupVolume->isNotEmpty(), fn (MarkdownBuilder $md) => $this->buildVolumeSection($md, $summary))
            ->when(! empty($summary->strengthProgression), fn (MarkdownBuilder $md) => $this->buildProgressionSection($md, $summary))
            ->when($summary->activeInjuries->isNotEmpty(), fn (MarkdownBuilder $md) => $md
                ->heading('Active Injuries', 2)
                ->each($summary->activeInjuries, fn (array $injury, MarkdownBuilder $md) => $md
                    ->listItem("**{$injury['body_part']}** ({$injury['injury_type']}) — since {$injury['started_at']}"))
                ->blankLine())
            ->toString();
    }

    protected function buildSessionLoadSection(MarkdownBuilder $md, WorkloadSummary $summary): void
    {
        $load = $summary->sessionLoad;

        $md->heading('Session Load', 2)
            ->tableHeader(['Metric', 'Value'])
            ->tableRow(['Weekly Total (sRPE)', $load->currentWeeklyTotal])
            ->tableRow(['Sessions This Week', $load->currentSessionCount])
            ->tableRow(['Monotony', $load->monotony])
            ->tableRow(['Strain', $load->strain])
            ->tableRow(['Week-over-Week Change', "{$load->weekOverWeekChangePct}%"])
            ->blankLine();

        if (! empty($load->previousWeeks)) {
            $md->heading('Previous Weeks', 3)
                ->tableHeader(['Week', 'Total Load', 'Sessions'])
                ->each($load->previousWeeks, fn (object $week, MarkdownBuilder $md) => $md
                    ->tableRow([$week->weekOffset, $week->totalLoad, $week->sessionCount]))
                ->blankLine();
        }
    }

    protected function buildVolumeSection(MarkdownBuilder $md, WorkloadSummary $summary): void
    {
        $md->heading('Muscle Group Volume', 2)
            ->tableHeader(['Muscle Group', 'Current Sets', '4-Week Avg', 'Trend'])
            ->each($summary->muscleGroupVolume, function (object $volume, MarkdownBuilder $md): void {
                $trendIcon = match ($volume->trend->value) {
                    'increasing' => "\u{2191}",
                    'decreasing' => "\u{2193}",
                    default => "\u{2192}",
                };
                $md->tableRow([$volume->label, $volume->currentWeekSets, $volume->fourWeekAverageSets, "{$trendIcon} {$volume->trend->value}"]);
            })
            ->blankLine();
    }

    protected function buildProgressionSection(MarkdownBuilder $md, WorkloadSummary $summary): void
    {
        $md->heading('Strength Progression', 2)
            ->tableHeader(['Exercise', 'Current e1RM', 'Previous e1RM', 'Change'])
            ->each($summary->strengthProgression, function (object $progression, MarkdownBuilder $md): void {
                $previous = $progression->previousE1RM !== null ? number_format($progression->previousE1RM, 1) : '-';
                $change = $progression->changePct !== null ? "{$progression->changePct}%" : '-';
                $md->tableRow([$progression->exerciseName, $progression->currentE1RM, $previous, $change]);
            })
            ->blankLine();
    }
}
