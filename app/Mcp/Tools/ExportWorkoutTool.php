<?php

namespace App\Mcp\Tools;

use App\Actions\ExportWorkoutFit;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class ExportWorkoutTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Export a workout as a Garmin FIT file. Returns base64-encoded binary data that can be imported into Garmin Connect or other FIT-compatible tools.
    MARKDOWN;

    public function __construct(
        private ExportWorkoutFit $export,
    ) {}

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'workout_id' => 'required|integer',
        ]);

        $user = $request->user();

        $workout = $user->workouts()->find($validated['workout_id']);

        if (! $workout) {
            return Response::error('Workout not found or access denied.');
        }

        $fitData = $this->export->execute($workout);

        $date = $workout->scheduled_at?->format('Y-m-d') ?? now()->format('Y-m-d');
        $slug = \Illuminate\Support\Str::slug($workout->name);

        return Response::structured([
            'success' => true,
            'filename' => "{$date}-{$slug}.fit",
            'data' => base64_encode($fitData),
            'content_type' => 'application/octet-stream',
        ]);
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'workout_id' => $schema->integer()->description('The ID of the workout to export as a FIT file'),
        ];
    }
}
