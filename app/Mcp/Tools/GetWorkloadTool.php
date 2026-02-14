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
        Get the authenticated user's muscle group workload data based on completed workouts from the last 28 days.

        Returns per-muscle-group load with ACWR (Acute:Chronic Workload Ratio) and zone indicators:
        - **undertraining** (ACWR < 0.8): Not enough stimulus for adaptation
        - **sweet_spot** (0.8–1.3): Optimal training zone for progress
        - **caution** (1.3–1.5): Elevated injury risk, consider reducing load
        - **danger** (> 1.5): High injury risk, strongly recommend reducing load

        The response includes a `data_span_days` field (0–28) indicating how many days of workout history are available. When `data_span_days` is below 28, ACWR values may be unreliable because the chronic load is calculated over a fixed 4-week window regardless of actual data coverage. Treat zone classifications with caution when data is incomplete.

        Use this data to:
        - Balance workouts across muscle groups
        - Avoid overloading muscles in caution/danger zones
        - Identify undertrained muscle groups to prioritize
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
