<?php

namespace App\Ai\Tools;

use App\Actions\ExportWorkoutFit;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class ExportWorkoutTool implements Tool
{
    public function __construct(
        private ExportWorkoutFit $export,
    ) {}

    public function description(): string
    {
        return 'Export a workout as a Garmin FIT file. Returns base64-encoded binary data that can be imported into Garmin Connect.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'workout_id' => $schema->integer()->description('The ID of the workout to export as a FIT file'),
        ];
    }

    public function handle(Request $request): string
    {
        $user = auth()->user();
        $workout = $user->workouts()->find($request['workout_id']);

        if (! $workout) {
            return json_encode(['error' => 'Workout not found or access denied.']);
        }

        $fitData = $this->export->execute($workout);

        $date = $workout->scheduled_at?->format('Y-m-d') ?? now()->format('Y-m-d');
        $slug = Str::slug($workout->name);

        return json_encode([
            'success' => true,
            'filename' => "{$date}-{$slug}.fit",
            'data' => base64_encode($fitData),
            'content_type' => 'application/octet-stream',
        ]);
    }
}
