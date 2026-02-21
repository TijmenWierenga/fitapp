<?php

namespace App\Ai\Tools;

use App\Actions\UpdateStructuredWorkout;
use App\DataTransferObjects\Workout\SectionData;
use App\Enums\Workout\Activity;
use App\Mcp\Tools\WorkoutResponseFormatter;
use App\Mcp\Tools\WorkoutSchemaBuilder;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class UpdateWorkoutTool implements Tool
{
    public function __construct(
        private WorkoutSchemaBuilder $schemaBuilder,
        private UpdateStructuredWorkout $updateStructuredWorkout,
    ) {}

    public function description(): string
    {
        return 'Update an existing workout. You can update the name, activity, scheduled time, notes, or sections. If sections is provided, the existing structure will be replaced entirely.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'workout_id' => $schema->integer()->description('The ID of the workout to update'),
            'name' => $schema->string()->description('The new name for the workout')->nullable(),
            'activity' => $schema->string()->enum(Activity::class)->description('The new activity type.')->nullable(),
            'scheduled_at' => $schema->string()->description('The new scheduled date and time (in user\'s timezone)')->nullable(),
            'notes' => $schema->string()->description('The new notes for the workout')->nullable(),
            'sections' => $schema->array()->items($this->schemaBuilder->section())->description('Replace entire workout structure with new sections/blocks/exercises.')->nullable(),
        ];
    }

    public function handle(Request $request): string
    {
        $user = auth()->user();
        $workout = $user->workouts()->find($request['workout_id']);

        if (! $workout) {
            return json_encode(['error' => 'Workout not found or access denied.']);
        }

        if ($user->cannot('update', $workout)) {
            return json_encode(['error' => 'You do not have permission to update this workout.']);
        }

        $updateData = [];

        if ($request->has('name')) {
            $updateData['name'] = $request['name'];
        }

        if ($request->has('activity')) {
            $updateData['activity'] = Activity::from($request['activity']);
        }

        if ($request->has('scheduled_at')) {
            $updateData['scheduled_at'] = CarbonImmutable::parse($request['scheduled_at'], $user->getTimezoneObject())->utc();
        }

        if ($request->has('notes')) {
            $updateData['notes'] = $request['notes'];
        }

        $workout->update($updateData);

        if ($request->has('sections') && is_array($request['sections'])) {
            $sections = collect($request['sections'])
                ->map(fn (array $section): SectionData => SectionData::fromArray($section));

            $this->updateStructuredWorkout->execute($workout, $sections);
        }

        return json_encode([
            'success' => true,
            'workout' => WorkoutResponseFormatter::format($workout->fresh(), $user),
            'message' => 'Workout updated successfully',
        ]);
    }
}
