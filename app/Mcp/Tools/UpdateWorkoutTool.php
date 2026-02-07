<?php

namespace App\Mcp\Tools;

use App\Actions\CreateStructuredWorkout;
use App\Enums\Workout\Activity;
use App\Enums\Workout\BlockType;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class UpdateWorkoutTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Update an existing workout. Only workouts that have not been completed can be updated.

        You can update the name, activity, scheduled time, notes, or sections. Only provide the fields you want to change.

        If `sections` is provided, the existing structure will be replaced entirely with the new sections/blocks/exercises.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'workout_id' => 'required|integer',
            'name' => 'sometimes|string|max:255',
            'activity' => ['sometimes', Rule::enum(Activity::class)],
            'scheduled_at' => 'sometimes|date',
            'notes' => 'nullable|string|max:5000',
            'sections' => 'sometimes|array',
            'sections.*.name' => 'required|string|max:255',
            'sections.*.order' => 'required|integer|min:0',
            'sections.*.notes' => 'nullable|string|max:5000',
            'sections.*.blocks' => 'sometimes|array',
            'sections.*.blocks.*.block_type' => ['required', Rule::enum(BlockType::class)],
            'sections.*.blocks.*.order' => 'required|integer|min:0',
            'sections.*.blocks.*.rounds' => 'nullable|integer|min:1',
            'sections.*.blocks.*.rest_between_exercises' => 'nullable|integer|min:0',
            'sections.*.blocks.*.rest_between_rounds' => 'nullable|integer|min:0',
            'sections.*.blocks.*.time_cap' => 'nullable|integer|min:0',
            'sections.*.blocks.*.work_interval' => 'nullable|integer|min:0',
            'sections.*.blocks.*.rest_interval' => 'nullable|integer|min:0',
            'sections.*.blocks.*.notes' => 'nullable|string|max:5000',
            'sections.*.blocks.*.exercises' => 'sometimes|array',
            'sections.*.blocks.*.exercises.*.name' => 'required|string|max:255',
            'sections.*.blocks.*.exercises.*.order' => 'required|integer|min:0',
            'sections.*.blocks.*.exercises.*.type' => 'required|in:strength,cardio,duration',
            'sections.*.blocks.*.exercises.*.notes' => 'nullable|string|max:5000',
            // Strength exercise fields
            'sections.*.blocks.*.exercises.*.target_sets' => 'nullable|integer|min:1',
            'sections.*.blocks.*.exercises.*.target_reps_min' => 'nullable|integer|min:0',
            'sections.*.blocks.*.exercises.*.target_reps_max' => 'nullable|integer|min:0',
            'sections.*.blocks.*.exercises.*.target_weight' => 'nullable|numeric|min:0',
            'sections.*.blocks.*.exercises.*.target_tempo' => 'nullable|string|max:20',
            'sections.*.blocks.*.exercises.*.rest_after' => 'nullable|integer|min:0',
            // Cardio exercise fields
            'sections.*.blocks.*.exercises.*.target_duration' => 'nullable|integer|min:0',
            'sections.*.blocks.*.exercises.*.target_distance' => 'nullable|numeric|min:0',
            'sections.*.blocks.*.exercises.*.target_pace_min' => 'nullable|integer|min:0',
            'sections.*.blocks.*.exercises.*.target_pace_max' => 'nullable|integer|min:0',
            'sections.*.blocks.*.exercises.*.target_heart_rate_zone' => 'nullable|integer|min:1|max:5',
            'sections.*.blocks.*.exercises.*.target_heart_rate_min' => 'nullable|integer|min:0',
            'sections.*.blocks.*.exercises.*.target_heart_rate_max' => 'nullable|integer|min:0',
            'sections.*.blocks.*.exercises.*.target_power' => 'nullable|integer|min:0',
            // Shared field
            'sections.*.blocks.*.exercises.*.target_rpe' => 'nullable|numeric|min:1|max:10',
        ], [
            'activity.Enum' => 'Invalid activity type. See available activity values.',
            'scheduled_at.date' => 'Please provide a valid date and time.',
        ]);

        $user = $request->user();

        $workout = $user->workouts()->find($validated['workout_id']);

        if (! $workout) {
            return Response::error('Workout not found or access denied');
        }

        try {
            Gate::forUser($user)->authorize('update', $workout);
        } catch (AuthorizationException) {
            return Response::error('Cannot update completed workouts');
        }

        $updateData = [];

        if (isset($validated['name'])) {
            $updateData['name'] = $validated['name'];
        }

        if (isset($validated['activity'])) {
            $updateData['activity'] = Activity::from($validated['activity']);
        }

        if (isset($validated['scheduled_at'])) {
            $updateData['scheduled_at'] = CarbonImmutable::parse($validated['scheduled_at'], $user->getTimezoneObject())->utc();
        }

        if (array_key_exists('notes', $validated)) {
            $updateData['notes'] = $validated['notes'];
        }

        $workout->update($updateData);

        if (isset($validated['sections'])) {
            // Delete existing sections (cascade handles blocks and exercises)
            $this->deleteExistingStructure($workout);

            // Recreate structure using the action's section-building logic
            $action = app(CreateStructuredWorkout::class);
            $this->buildSections($action, $workout, $validated['sections']);
        }

        return Response::text(json_encode([
            'success' => true,
            'workout' => WorkoutResponseFormatter::format($workout->fresh(), $user),
            'message' => 'Workout updated successfully',
        ]));
    }

    protected function deleteExistingStructure(\App\Models\Workout $workout): void
    {
        // Collect exerciseable IDs before deleting to clean up polymorphic records
        $workout->load('sections.blocks.exercises');

        foreach ($workout->sections as $section) {
            foreach ($section->blocks as $block) {
                foreach ($block->exercises as $exercise) {
                    $exercise->exerciseable?->delete();
                }
            }
        }

        // Cascade delete handles block_exercises and blocks via FK constraints
        $workout->sections()->delete();
    }

    /**
     * @param  array<int, array<string, mixed>>  $sections
     */
    protected function buildSections(CreateStructuredWorkout $action, \App\Models\Workout $workout, array $sections): void
    {
        foreach ($sections as $sectionData) {
            $section = $workout->sections()->create([
                'name' => $sectionData['name'],
                'order' => $sectionData['order'],
                'notes' => $sectionData['notes'] ?? null,
            ]);

            foreach ($sectionData['blocks'] ?? [] as $blockData) {
                $block = $section->blocks()->create([
                    'block_type' => \App\Enums\Workout\BlockType::from($blockData['block_type']),
                    'order' => $blockData['order'],
                    'rounds' => $blockData['rounds'] ?? null,
                    'rest_between_exercises' => $blockData['rest_between_exercises'] ?? null,
                    'rest_between_rounds' => $blockData['rest_between_rounds'] ?? null,
                    'time_cap' => $blockData['time_cap'] ?? null,
                    'work_interval' => $blockData['work_interval'] ?? null,
                    'rest_interval' => $blockData['rest_interval'] ?? null,
                    'notes' => $blockData['notes'] ?? null,
                ]);

                foreach ($blockData['exercises'] ?? [] as $exerciseData) {
                    $exerciseable = match ($exerciseData['type']) {
                        'strength' => \App\Models\StrengthExercise::create([
                            'target_sets' => $exerciseData['target_sets'] ?? null,
                            'target_reps_min' => $exerciseData['target_reps_min'] ?? null,
                            'target_reps_max' => $exerciseData['target_reps_max'] ?? null,
                            'target_weight' => $exerciseData['target_weight'] ?? null,
                            'target_rpe' => $exerciseData['target_rpe'] ?? null,
                            'target_tempo' => $exerciseData['target_tempo'] ?? null,
                            'rest_after' => $exerciseData['rest_after'] ?? null,
                        ]),
                        'cardio' => \App\Models\CardioExercise::create([
                            'target_duration' => $exerciseData['target_duration'] ?? null,
                            'target_distance' => $exerciseData['target_distance'] ?? null,
                            'target_pace_min' => $exerciseData['target_pace_min'] ?? null,
                            'target_pace_max' => $exerciseData['target_pace_max'] ?? null,
                            'target_heart_rate_zone' => $exerciseData['target_heart_rate_zone'] ?? null,
                            'target_heart_rate_min' => $exerciseData['target_heart_rate_min'] ?? null,
                            'target_heart_rate_max' => $exerciseData['target_heart_rate_max'] ?? null,
                            'target_power' => $exerciseData['target_power'] ?? null,
                        ]),
                        'duration' => \App\Models\DurationExercise::create([
                            'target_duration' => $exerciseData['target_duration'] ?? null,
                            'target_rpe' => $exerciseData['target_rpe'] ?? null,
                        ]),
                    };

                    $block->exercises()->create([
                        'name' => $exerciseData['name'],
                        'order' => $exerciseData['order'],
                        'exerciseable_type' => $exerciseable->getMorphClass(),
                        'exerciseable_id' => $exerciseable->getKey(),
                        'notes' => $exerciseData['notes'] ?? null,
                    ]);
                }
            }
        }
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'workout_id' => $schema->integer()->description('The ID of the workout to update'),
            'name' => $schema->string()->description('The new name for the workout')->nullable(),
            'activity' => $schema->string()->description('The new activity type (e.g., run, strength, cardio, hiit, bike, pool_swim, hike, yoga, etc.)')->nullable(),
            'scheduled_at' => $schema->string()->description('The new scheduled date and time (in user\'s timezone)')->nullable(),
            'notes' => $schema->string()->description('The new notes for the workout')->nullable(),
            'sections' => $schema->array()->description('Optional: replace entire workout structure with new sections/blocks/exercises. If provided, existing structure is deleted and replaced.')->nullable(),
        ];
    }
}
