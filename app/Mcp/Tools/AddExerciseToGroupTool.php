<?php

namespace App\Mcp\Tools;

use App\Enums\Workout\BlockType;
use App\Models\ExerciseEntry;
use App\Models\ExerciseGroup;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Gate;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class AddExerciseToGroupTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Add an exercise entry to an exercise group block. The block must be of type `exercise_group`. Specify sets, reps, weight, RPE target, and rest between sets.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'workout_id' => 'required|integer',
            'exercise_group_block_id' => 'required|integer',
            'exercise_id' => 'required|integer|exists:exercises,id',
            'position' => 'nullable|integer|min:0',
            'sets' => 'required|integer|min:1',
            'reps' => 'nullable|integer|min:1',
            'duration_seconds' => 'nullable|integer|min:1',
            'weight_kg' => 'nullable|numeric|min:0',
            'rpe_target' => 'nullable|integer|min:1|max:10',
            'rest_between_sets_seconds' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:1000',
        ], [
            'exercise_id.exists' => 'Exercise not found. Use get-exercise-catalog to browse available exercises.',
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

        $block = $workout->allBlocks()
            ->where('type', BlockType::ExerciseGroup)
            ->find($validated['exercise_group_block_id']);

        if (! $block) {
            return Response::error('Exercise group block not found in this workout');
        }

        /** @var ExerciseGroup $exerciseGroup */
        $exerciseGroup = $block->blockable;

        $position = $validated['position'] ?? (($exerciseGroup->entries()->max('position') ?? -1) + 1);

        $entry = ExerciseEntry::create([
            'exercise_group_id' => $exerciseGroup->id,
            'exercise_id' => $validated['exercise_id'],
            'position' => $position,
            'sets' => $validated['sets'],
            'reps' => $validated['reps'] ?? null,
            'duration_seconds' => $validated['duration_seconds'] ?? null,
            'weight_kg' => $validated['weight_kg'] ?? null,
            'rpe_target' => $validated['rpe_target'] ?? null,
            'rest_between_sets_seconds' => $validated['rest_between_sets_seconds'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        $entry->load('exercise');

        return Response::text(json_encode([
            'success' => true,
            'entry' => [
                'id' => $entry->id,
                'exercise_group_id' => $entry->exercise_group_id,
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
            ],
            'message' => 'Exercise added to group successfully',
        ]));
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'workout_id' => $schema->integer()->description('The ID of the workout')->required(),
            'exercise_group_block_id' => $schema->integer()->description('The ID of the WorkoutBlock that contains the exercise group')->required(),
            'exercise_id' => $schema->integer()->description('The ID of the exercise to add (use get-exercise-catalog to find exercises)')->required(),
            'sets' => $schema->integer()->description('Number of sets (minimum 1)')->required(),
            'position' => $schema->integer()->description('Position within the group (defaults to next available)')->nullable(),
            'reps' => $schema->integer()->description('Number of reps per set')->nullable(),
            'duration_seconds' => $schema->integer()->description('Duration per set in seconds (alternative to reps)')->nullable(),
            'weight_kg' => $schema->number()->description('Weight in kilograms')->nullable(),
            'rpe_target' => $schema->integer()->description('Target RPE (1-10)')->nullable(),
            'rest_between_sets_seconds' => $schema->integer()->description('Rest between sets in seconds')->nullable(),
            'notes' => $schema->string()->description('Optional notes for this exercise')->nullable(),
        ];
    }
}
