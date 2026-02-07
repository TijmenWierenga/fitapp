<?php

namespace App\Mcp\Tools;

use App\Services\MuscleLoad\MuscleRecoveryService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[IsIdempotent]
class GetRecoveryStatusTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Check muscle recovery status for the authenticated user. Returns fatigue scores and recovery status for all muscle groups based on recent completed workouts.
    MARKDOWN;

    public function __construct(private MuscleRecoveryService $recoveryService) {}

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $user = $request->user();

        $recoveryStatus = $this->recoveryService->getRecoveryStatus($user);

        $data = collect($recoveryStatus)
            ->map(fn (array $status): array => [
                'muscle_group' => $status['muscle_group']->value,
                'muscle_label' => $status['muscle_group']->label(),
                'fatigue_score' => round($status['fatigue_score'], 2),
                'status' => $status['status'],
                'ready_for_heavy' => $status['ready_for_heavy'],
            ])
            ->sortByDesc('fatigue_score')
            ->values()
            ->all();

        return Response::text(json_encode([
            'success' => true,
            'recovery_status' => $data,
        ]));
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
