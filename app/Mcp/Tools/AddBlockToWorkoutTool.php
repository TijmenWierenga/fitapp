<?php

namespace App\Mcp\Tools;

use App\Enums\Workout\BlockType;
use App\Enums\Workout\ExerciseGroupType;
use App\Enums\Workout\IntervalIntensity;
use App\Models\ExerciseGroup;
use App\Models\IntervalBlock;
use App\Models\NoteBlock;
use App\Models\RestBlock;
use App\Models\WorkoutBlock;
use App\Rules\MaxBlockDepth;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class AddBlockToWorkoutTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Add a block to a workout. Blocks can be: group (container), interval (cardio interval), exercise_group (exercises), rest (rest period), or note (text note). Blocks can be nested up to 3 levels deep using parent_id.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'workout_id' => 'required|integer',
            'parent_id' => ['nullable', 'integer', new MaxBlockDepth],
            'type' => ['required', Rule::enum(BlockType::class)],
            'position' => 'nullable|integer|min:0',
            'label' => 'nullable|string|max:255',
            'repeat_count' => 'nullable|integer|min:1',
            'rest_between_repeats_seconds' => 'nullable|integer|min:0',
            // Interval fields
            'duration_seconds' => 'nullable|integer|min:1',
            'distance_meters' => 'nullable|integer|min:1',
            'target_pace_seconds_per_km' => 'nullable|integer|min:1',
            'target_heart_rate_zone' => 'nullable|integer|min:1|max:5',
            'intensity' => ['nullable', Rule::enum(IntervalIntensity::class)],
            // Exercise group fields
            'group_type' => ['nullable', Rule::enum(ExerciseGroupType::class)],
            'rounds' => 'nullable|integer|min:1',
            'rest_between_rounds_seconds' => 'nullable|integer|min:0',
            // Note fields
            'content' => 'nullable|string|max:5000',
        ]);

        $user = $request->user();
        $type = BlockType::from($validated['type']);

        $workout = $user->workouts()->find($validated['workout_id']);

        if (! $workout) {
            return Response::error('Workout not found or access denied');
        }

        try {
            Gate::forUser($user)->authorize('update', $workout);
        } catch (AuthorizationException) {
            return Response::error('Cannot modify a completed workout');
        }

        // Validate parent belongs to workout
        if (! empty($validated['parent_id'])) {
            $parent = $workout->allBlocks()->where('type', BlockType::Group)->find($validated['parent_id']);

            if (! $parent) {
                return Response::error('Parent block not found or is not a group block in this workout');
            }
        }

        // Calculate position if not provided
        $position = $validated['position'] ?? $this->nextPosition($workout, $validated['parent_id'] ?? null);

        $block = DB::transaction(function () use ($validated, $workout, $type, $position): WorkoutBlock {
            $blockableType = null;
            $blockableId = null;

            if ($type !== BlockType::Group) {
                [$blockableType, $blockableId] = $this->createBlockable($type, $validated);
            }

            return WorkoutBlock::create([
                'workout_id' => $workout->id,
                'parent_id' => $validated['parent_id'] ?? null,
                'type' => $type,
                'position' => $position,
                'label' => $validated['label'] ?? null,
                'repeat_count' => $validated['repeat_count'] ?? 1,
                'rest_between_repeats_seconds' => $validated['rest_between_repeats_seconds'] ?? null,
                'blockable_type' => $blockableType,
                'blockable_id' => $blockableId,
            ]);
        });

        $block->load('blockable');

        return Response::text(json_encode([
            'success' => true,
            'block' => [
                'id' => $block->id,
                'workout_id' => $block->workout_id,
                'parent_id' => $block->parent_id,
                'type' => $block->type->value,
                'position' => $block->position,
                'label' => $block->label,
                'repeat_count' => $block->repeat_count,
                'rest_between_repeats_seconds' => $block->rest_between_repeats_seconds,
                'blockable' => $block->blockable?->toArray(),
            ],
            'message' => 'Block added successfully',
        ]));
    }

    /**
     * Create the polymorphic blockable record.
     *
     * @param  array<string, mixed>  $validated
     * @return array{0: string, 1: int}
     */
    protected function createBlockable(BlockType $type, array $validated): array
    {
        return match ($type) {
            BlockType::Interval => $this->createIntervalBlock($validated),
            BlockType::ExerciseGroup => $this->createExerciseGroup($validated),
            BlockType::Rest => $this->createRestBlock($validated),
            BlockType::Note => $this->createNoteBlock($validated),
            default => throw new \InvalidArgumentException("Unexpected block type: {$type->value}"),
        };
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{0: string, 1: int}
     */
    protected function createIntervalBlock(array $validated): array
    {
        $block = IntervalBlock::create([
            'duration_seconds' => $validated['duration_seconds'] ?? null,
            'distance_meters' => $validated['distance_meters'] ?? null,
            'target_pace_seconds_per_km' => $validated['target_pace_seconds_per_km'] ?? null,
            'target_heart_rate_zone' => $validated['target_heart_rate_zone'] ?? null,
            'intensity' => isset($validated['intensity']) ? IntervalIntensity::from($validated['intensity']) : null,
        ]);

        return ['interval_block', $block->id];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{0: string, 1: int}
     */
    protected function createExerciseGroup(array $validated): array
    {
        $group = ExerciseGroup::create([
            'group_type' => isset($validated['group_type']) ? ExerciseGroupType::from($validated['group_type']) : ExerciseGroupType::Straight,
            'rounds' => $validated['rounds'] ?? 1,
            'rest_between_rounds_seconds' => $validated['rest_between_rounds_seconds'] ?? null,
        ]);

        return ['exercise_group', $group->id];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{0: string, 1: int}
     */
    protected function createRestBlock(array $validated): array
    {
        $block = RestBlock::create([
            'duration_seconds' => $validated['duration_seconds'] ?? null,
        ]);

        return ['rest_block', $block->id];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{0: string, 1: int}
     */
    protected function createNoteBlock(array $validated): array
    {
        $block = NoteBlock::create([
            'content' => $validated['content'] ?? '',
        ]);

        return ['note_block', $block->id];
    }

    /**
     * Calculate the next position for a block at the given level.
     */
    protected function nextPosition(\App\Models\Workout $workout, ?int $parentId): int
    {
        $maxPosition = $workout->allBlocks()
            ->where('parent_id', $parentId)
            ->max('position');

        return ($maxPosition ?? -1) + 1;
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'workout_id' => $schema->integer()->description('The ID of the workout to add the block to')->required(),
            'parent_id' => $schema->integer()->description('The ID of a group block to nest this block under (for nesting up to 3 levels)')->nullable(),
            'type' => $schema->string()
                ->enum(array_map(fn (BlockType $t): string => $t->value, BlockType::cases()))
                ->description('The block type: group, interval, exercise_group, rest, or note')
                ->required(),
            'position' => $schema->integer()->description('Position within siblings (defaults to next available)')->nullable(),
            'label' => $schema->string()->description('Optional label for the block')->nullable(),
            'repeat_count' => $schema->integer()->description('Number of times to repeat this block (default 1)')->nullable(),
            'rest_between_repeats_seconds' => $schema->integer()->description('Rest duration in seconds between repetitions')->nullable(),
            // Interval fields
            'duration_seconds' => $schema->integer()->description('Duration in seconds (for interval or rest blocks)')->nullable(),
            'distance_meters' => $schema->integer()->description('Distance in meters (for interval blocks)')->nullable(),
            'target_pace_seconds_per_km' => $schema->integer()->description('Target pace in seconds per km (for interval blocks)')->nullable(),
            'target_heart_rate_zone' => $schema->integer()->description('Target heart rate zone 1-5 (for interval blocks)')->nullable(),
            'intensity' => $schema->string()
                ->enum(array_map(fn (IntervalIntensity $i): string => $i->value, IntervalIntensity::cases()))
                ->description('Intensity level (for interval blocks): easy, moderate, threshold, tempo, vo2max, sprint')
                ->nullable(),
            // Exercise group fields
            'group_type' => $schema->string()
                ->enum(array_map(fn (ExerciseGroupType $t): string => $t->value, ExerciseGroupType::cases()))
                ->description('Exercise group type: straight, superset, circuit, emom, amrap')
                ->nullable(),
            'rounds' => $schema->integer()->description('Number of rounds (for exercise group blocks, default 1)')->nullable(),
            'rest_between_rounds_seconds' => $schema->integer()->description('Rest between rounds in seconds (for exercise group blocks)')->nullable(),
            // Note fields
            'content' => $schema->string()->description('Text content (for note blocks)')->nullable(),
        ];
    }
}
