<?php

namespace App\Mcp\Tools;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[IsIdempotent]
class ReorderBlocksTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Reorder blocks within a workout. Provide an array of block IDs in the desired order. All block IDs must belong to the same workout and share the same parent.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'workout_id' => 'required|integer',
            'block_ids' => 'required|array|min:1',
            'block_ids.*' => 'integer',
        ], [
            'block_ids.required' => 'You must provide an array of block IDs in the desired order.',
        ]);

        $user = $request->user();

        $workout = $user->workouts()->find($validated['workout_id']);

        if (! $workout) {
            return Response::error('Workout not found or access denied');
        }

        try {
            Gate::forUser($user)->authorize('update', $workout);
        } catch (AuthorizationException) {
            return Response::error('Cannot modify a completed workout');
        }

        $blockIds = $validated['block_ids'];
        $blocks = $workout->allBlocks()->whereIn('id', $blockIds)->get();

        if ($blocks->count() !== count($blockIds)) {
            return Response::error('One or more block IDs do not belong to this workout');
        }

        // Verify all blocks share the same parent
        $parentIds = $blocks->pluck('parent_id')->unique();

        if ($parentIds->count() > 1) {
            return Response::error('All blocks must share the same parent');
        }

        DB::transaction(function () use ($blockIds): void {
            foreach ($blockIds as $position => $blockId) {
                \App\Models\WorkoutBlock::where('id', $blockId)->update(['position' => $position]);
            }
        });

        return Response::text(json_encode([
            'success' => true,
            'message' => 'Blocks reordered successfully',
        ]));
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'workout_id' => $schema->integer()->description('The ID of the workout')->required(),
            'block_ids' => $schema->array()->description('Array of block IDs in the desired order. All must share the same parent.')->required(),
        ];
    }
}
