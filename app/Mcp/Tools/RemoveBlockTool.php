<?php

namespace App\Mcp\Tools;

use App\Models\WorkoutBlock;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive]
class RemoveBlockTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Remove a block and all its children from a workout. This is destructive and cannot be undone.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'workout_id' => 'required|integer',
            'block_id' => 'required|integer',
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

        $block = $workout->allBlocks()->find($validated['block_id']);

        if (! $block) {
            return Response::error('Block not found in this workout');
        }

        DB::transaction(function () use ($block): void {
            $this->deleteBlockRecursive($block);
        });

        return Response::text(json_encode([
            'success' => true,
            'message' => 'Block removed successfully',
        ]));
    }

    /**
     * Recursively delete a block, its children, and all blockable records.
     */
    protected function deleteBlockRecursive(WorkoutBlock $block): void
    {
        // Delete children first (depth-first)
        foreach ($block->children as $child) {
            $this->deleteBlockRecursive($child);
        }

        // Delete the blockable record (polymorphic, no FK cascade)
        $block->blockable?->delete();

        // Delete the block itself
        $block->delete();
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'workout_id' => $schema->integer()->description('The ID of the workout')->required(),
            'block_id' => $schema->integer()->description('The ID of the block to remove')->required(),
        ];
    }
}
