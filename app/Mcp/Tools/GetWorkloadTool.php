<?php

namespace App\Mcp\Tools;

use App\Actions\CalculateWorkload;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class GetWorkloadTool extends Tool
{
    public function __construct(
        private CalculateWorkload $calculateWorkload,
    ) {}

    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Get the authenticated user's training workload data based on completed workouts from up to 56 days.

        Returns three evidence-based metrics:

        **Session Load (sRPE):** Weekly training load calculated as duration x RPE for each session.
        - Includes monotony (variation) and strain indicators
        - Week-over-week change percentage with >15% warning threshold
        - Requires workouts to have duration recorded

        **Muscle Group Volume:** Weekly set counts per muscle group from strength exercises.
        - Distributed via load factors (primary 1.0, secondary 0.5)
        - 4-week average and trend (increasing/stable/decreasing)

        **Strength Progression:** Estimated 1RM changes over 4-week periods.
        - Uses Epley formula: weight x (1 + reps/30)
        - Compares current 28-day period vs previous 28-day period

        Use this data to:
        - Monitor overall training load and avoid rapid increases
        - Balance volume across muscle groups
        - Track strength progression over time
        - Consider active injuries when planning exercises near affected areas
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $user = $request->user();
        $summary = $this->calculateWorkload->execute($user);

        return Response::structured($summary->toArray());
    }
}
