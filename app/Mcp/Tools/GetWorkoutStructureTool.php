<?php

namespace App\Mcp\Tools;

use App\Models\ExerciseGroup;
use App\Models\WorkoutBlock;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Gate;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[IsIdempotent]
class GetWorkoutStructureTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        View the full nested block tree of a workout. Returns the hierarchical structure showing all blocks, their types, content, and children.
    MARKDOWN;

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

        $workout->load([
            'blockTree.blockable',
            'blockTree.nestedChildren.blockable',
            'blockTree.nestedChildren.nestedChildren.blockable',
        ]);

        $tree = $workout->blockTree->map(fn (WorkoutBlock $block): array => $this->formatBlock($block))->all();

        return Response::text(json_encode([
            'success' => true,
            'workout_id' => $workout->id,
            'workout_name' => $workout->name,
            'blocks' => $tree,
        ]));
    }

    /**
     * Recursively format a block and its children.
     *
     * @return array<string, mixed>
     */
    protected function formatBlock(WorkoutBlock $block): array
    {
        $data = [
            'id' => $block->id,
            'type' => $block->type->value,
            'position' => $block->position,
            'label' => $block->label,
            'repeat_count' => $block->repeat_count,
            'rest_between_repeats_seconds' => $block->rest_between_repeats_seconds,
        ];

        if ($block->blockable) {
            $data['content'] = $this->formatBlockable($block);
        }

        $children = $block->relationLoaded('nestedChildren') ? $block->nestedChildren : $block->children;

        if ($children->isNotEmpty()) {
            $data['children'] = $children->map(fn (WorkoutBlock $child): array => $this->formatBlock($child))->all();
        }

        return $data;
    }

    /**
     * Format the polymorphic blockable content.
     *
     * @return array<string, mixed>
     */
    protected function formatBlockable(WorkoutBlock $block): array
    {
        $blockable = $block->blockable;

        if ($blockable instanceof \App\Models\IntervalBlock) {
            return [
                'duration_seconds' => $blockable->duration_seconds,
                'distance_meters' => $blockable->distance_meters,
                'target_pace_seconds_per_km' => $blockable->target_pace_seconds_per_km,
                'target_heart_rate_zone' => $blockable->target_heart_rate_zone,
                'intensity' => $blockable->intensity?->value,
            ];
        }

        if ($blockable instanceof ExerciseGroup) {
            $blockable->load('entries.exercise');

            return [
                'group_type' => $blockable->group_type->value,
                'rounds' => $blockable->rounds,
                'rest_between_rounds_seconds' => $blockable->rest_between_rounds_seconds,
                'entries' => $blockable->entries->map(fn ($entry): array => [
                    'id' => $entry->id,
                    'exercise_id' => $entry->exercise_id,
                    'exercise_name' => $entry->exercise->name,
                    'position' => $entry->position,
                    'sets' => $entry->sets,
                    'reps' => $entry->reps,
                    'duration_seconds' => $entry->duration_seconds,
                    'weight_kg' => $entry->weight_kg,
                    'rpe_target' => $entry->rpe_target,
                    'rest_between_sets_seconds' => $entry->rest_between_sets_seconds,
                    'notes' => $entry->notes,
                ])->all(),
            ];
        }

        if ($blockable instanceof \App\Models\RestBlock) {
            return [
                'duration_seconds' => $blockable->duration_seconds,
            ];
        }

        if ($blockable instanceof \App\Models\NoteBlock) {
            return [
                'content' => $blockable->content,
            ];
        }

        return [];
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'workout_id' => $schema->integer()->description('The ID of the workout to view'),
        ];
    }
}
