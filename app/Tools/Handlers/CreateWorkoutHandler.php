<?php

declare(strict_types=1);

namespace App\Tools\Handlers;

use App\Actions\CreateStructuredWorkout;
use App\DataTransferObjects\Workout\SectionData;
use App\Enums\Workout\Activity;
use App\Models\User;
use App\Tools\Input\CreateWorkoutInput;
use App\Tools\ToolResult;
use App\Tools\WorkoutResponseFormatter;
use App\Tools\WorkoutSchemaBuilder;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class CreateWorkoutHandler
{
    public function __construct(
        private WorkoutSchemaBuilder $schemaBuilder,
        private CreateStructuredWorkout $createStructuredWorkout,
    ) {}

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('The name/title of the workout (e.g., "Morning Run", "Leg Day")'),
            'activity' => $schema->string()->enum(Activity::class)->description('The activity type.'),
            'scheduled_at' => $schema->string()->description('The date and time when the workout is scheduled (in user\'s timezone)'),
            'notes' => $schema->string()->description('Optional Markdown notes for the workout.')->nullable(),
            'sections' => $schema->array()->items($this->schemaBuilder->section())->description('Required structured workout sections with blocks and exercises.'),
        ];
    }

    public function execute(User $user, CreateWorkoutInput $input): ToolResult
    {
        $scheduledAt = CarbonImmutable::parse($input->scheduledAt, $user->getTimezoneObject())->utc();

        $sections = collect($input->sections)
            ->map(fn (array $section): SectionData => SectionData::fromArray($section));

        $workout = $this->createStructuredWorkout->execute(
            user: $user,
            name: $input->name,
            activity: Activity::from($input->activity),
            scheduledAt: $scheduledAt,
            notes: $input->notes,
            sections: $sections,
        );

        return ToolResult::success([
            'workout' => WorkoutResponseFormatter::format($workout, $user),
            'message' => 'Workout created successfully',
        ]);
    }
}
