<?php

declare(strict_types=1);

namespace App\Tools\Handlers;

use App\Actions\ExportWorkoutFit;
use App\Models\User;
use App\Tools\Input\ExportWorkoutInput;
use App\Tools\ToolResult;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;

class ExportWorkoutHandler
{
    public function __construct(
        private ExportWorkoutFit $export,
    ) {}

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'workout_id' => $schema->integer()->description('The ID of the workout to export as a FIT file'),
        ];
    }

    public function execute(User $user, ExportWorkoutInput $input): ToolResult
    {
        $workout = $user->workouts()->find($input->workoutId);

        if (! $workout) {
            return ToolResult::error('Workout not found or access denied.');
        }

        $fitData = $this->export->execute($workout);

        $date = $workout->scheduled_at?->format('Y-m-d') ?? now()->format('Y-m-d');
        $slug = Str::slug($workout->name);

        return ToolResult::success([
            'filename' => "{$date}-{$slug}.fit",
            'data' => base64_encode($fitData),
            'content_type' => 'application/octet-stream',
        ]);
    }
}
