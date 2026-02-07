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
class SuggestTargetMusclesTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Get suggested target muscles based on recovery status. Returns muscle groups sorted by readiness (most recovered first), helping plan which muscles to target in the next workout.
    MARKDOWN;

    public function __construct(private MuscleRecoveryService $recoveryService) {}

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $user = $request->user();

        $suggestions = $this->recoveryService->suggestTargetMuscles($user);

        $data = array_map(fn (array $muscle): array => [
            'muscle_group' => $muscle['muscle_group']->value,
            'muscle_label' => $muscle['muscle_group']->label(),
            'fatigue_score' => round($muscle['fatigue_score'], 2),
            'status' => $muscle['status'],
            'ready_for_heavy' => $muscle['ready_for_heavy'],
        ], $suggestions);

        return Response::text(json_encode([
            'success' => true,
            'suggestions' => $data,
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
