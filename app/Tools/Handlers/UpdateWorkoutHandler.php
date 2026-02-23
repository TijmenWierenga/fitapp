<?php

declare(strict_types=1);

namespace App\Tools\Handlers;

use App\Actions\UpdateStructuredWorkout;
use App\DataTransferObjects\Workout\SectionData;
use App\Enums\Workout\Activity;
use App\Models\User;
use App\Tools\Input\UpdateWorkoutInput;
use App\Tools\ToolResult;
use App\Tools\WorkoutResponseFormatter;
use App\Tools\WorkoutSchemaBuilder;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class UpdateWorkoutHandler
{
    public function __construct(
        private WorkoutSchemaBuilder $schemaBuilder,
        private UpdateStructuredWorkout $updateStructuredWorkout,
    ) {}

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'workout_id' => $schema->integer()->description('The ID of the workout to update'),
            'name' => $schema->string()->description('The new name for the workout')->nullable(),
            'activity' => $schema->string()->enum(Activity::class)->description('The new activity type.')->nullable(),
            'scheduled_at' => $schema->string()->description('The new scheduled date and time (in user\'s timezone)')->nullable(),
            'notes' => $schema->string()->description('The new notes for the workout')->nullable(),
            'sections' => $schema->array()->items($this->schemaBuilder->section())->description('Replace entire workout structure with new sections/blocks/exercises. If provided, existing structure is deleted and replaced.')->nullable(),
        ];
    }

    public function execute(User $user, UpdateWorkoutInput $input): ToolResult
    {
        $workout = $user->workouts()->find($input->workoutId);

        if (! $workout) {
            return ToolResult::error('Workout not found or access denied.');
        }

        if ($user->cannot('update', $workout)) {
            return ToolResult::error('You do not have permission to update this workout.');
        }

        $updateData = [];

        if ($input->has('name')) {
            $updateData['name'] = $input->name;
        }

        if ($input->has('activity')) {
            $updateData['activity'] = Activity::from($input->activity);
        }

        if ($input->has('scheduled_at')) {
            $updateData['scheduled_at'] = CarbonImmutable::parse($input->scheduledAt, $user->getTimezoneObject())->utc();
        }

        if ($input->has('notes')) {
            $updateData['notes'] = $input->notes;
        }

        $workout->update($updateData);

        if ($input->has('sections') && is_array($input->sections)) {
            $sections = collect($input->sections)
                ->map(fn (array $section): SectionData => SectionData::fromArray($section));

            $this->updateStructuredWorkout->execute($workout, $sections);
        }

        return ToolResult::success([
            'workout' => WorkoutResponseFormatter::format($workout->fresh(), $user),
            'message' => 'Workout updated successfully',
        ]);
    }
}
