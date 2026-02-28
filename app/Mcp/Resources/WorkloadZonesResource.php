<?php

namespace App\Mcp\Resources;

use Laravel\Mcp\Enums\Role;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Annotations\Audience;
use Laravel\Mcp\Server\Annotations\Priority;
use Laravel\Mcp\Server\Resource;

#[Audience(Role::Assistant)]
#[Priority(0.5)]
class WorkloadZonesResource extends Resource
{
    /**
     * The resource URI.
     */
    protected string $uri = 'workout://workload-zones';

    /**
     * The resource's description.
     */
    protected string $description = <<<'MARKDOWN'
        ACWR (Acute:Chronic Workload Ratio) zone definitions and decision rules for safe workout programming.

        Read this resource before creating workout plans to avoid overtraining.
    MARKDOWN;

    /**
     * Handle the resource request.
     */
    public function handle(Request $request): Response
    {
        return Response::text($this->buildContent());
    }

    protected function buildContent(): string
    {
        return <<<'MARKDOWN'
            # Workload Zones (ACWR)

            ## Zone Definitions

            | Zone | ACWR Range | Interpretation |
            |---|---|---|
            | Undertraining | < 0.8 | Muscle group is under-stimulated — safe to increase volume |
            | Sweet Spot | 0.8–1.3 | Optimal training load — maintain current volume |
            | Caution | 1.3–1.5 | Elevated injury risk — reduce or hold volume |
            | Danger | > 1.5 | High injury risk — significantly reduce volume or rest |

            ## Decision Rules

            - **Avoid** programming heavy work for muscle groups in caution or danger zones
            - **Prioritize** undertrained muscle groups when balancing weekly plans
            - **Cross-reference** active injuries with muscle group load — if a muscle group near an injured body part is in caution/danger, flag this to the user
            - **Link exercises** to the exercise library (via `exercise_id`) to enable workload tracking
            MARKDOWN;
    }
}
