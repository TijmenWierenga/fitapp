<?php

namespace App\Mcp\Tools;

use App\Services\MuscleLoad\MuscleLoadCalculator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Gate;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class GetMuscleLoadPreviewTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Preview the per-muscle load breakdown for a workout based on its current block structure. Useful for balancing workout intensity across muscle groups.
    MARKDOWN;

    public function __construct(private MuscleLoadCalculator $calculator) {}

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'workout_id' => 'required|integer',
        ]);

        $user = $request->user();

        $workout = $user->workouts()->find($validated['workout_id']);

        if (! $workout) {
            return Response::error('Workout not found or access denied');
        }

        try {
            Gate::forUser($user)->authorize('view', $workout);
        } catch (AuthorizationException) {
            return Response::error('Workout not found or access denied');
        }

        $summary = $this->calculator->calculate($workout);

        $muscleLoads = collect($summary->all())
            ->map(fn (array $data, string $muscleKey): array => [
                'muscle_group' => $muscleKey,
                'total_load' => round($data['total'], 2),
                'sources' => array_map(fn (array $source): array => [
                    'description' => $source['description'],
                    'load' => round($source['load'], 2),
                ], $data['sources']),
            ])
            ->sortByDesc('total_load')
            ->values()
            ->all();

        return Response::text(json_encode([
            'success' => true,
            'workout_id' => $workout->id,
            'workout_name' => $workout->name,
            'total_load' => round($summary->totalLoad(), 2),
            'muscle_loads' => $muscleLoads,
        ]));
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'workout_id' => $schema->integer()->description('The ID of the workout to preview muscle load for'),
        ];
    }
}
