<?php

namespace App\Mcp\Tools;

use App\Enums\Workout\Sport;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Validator;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class CreateWorkoutTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Creates a new workout for the authenticated user. Use the notes field extensively to provide detailed workout instructions, pace targets, intensity zones, exercise lists, and any other guidance since v1 doesn't support step builders.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $user = $request->user();

        if (! $user) {
            return Response::error('Authentication required. Please provide a valid API token.');
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'sport' => ['required', 'string', 'in:running,strength,cardio,hiit'],
            'notes' => ['nullable', 'string'],
            'scheduled_at' => ['required', 'date_format:Y-m-d\TH:i:s'],
        ]);

        if ($validator->fails()) {
            return Response::error('Validation failed: '.json_encode($validator->errors()->toArray()));
        }

        $validated = $validator->validated();

        $workout = $user->workouts()->create([
            'name' => $validated['name'],
            'sport' => Sport::from($validated['sport']),
            'notes' => $validated['notes'] ?? null,
            'scheduled_at' => $validated['scheduled_at'],
        ]);

        return Response::json([
            'id' => $workout->id,
            'name' => $workout->name,
            'sport' => $workout->sport->value,
            'notes' => $workout->notes,
            'scheduled_at' => $user->toUserTimezone($workout->scheduled_at)->format('Y-m-d H:i:s'),
            'is_completed' => $workout->is_completed,
        ]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()
                ->description('The name/title of the workout (e.g., "Morning Run", "Leg Day")'),
            'sport' => $schema->enum(['running', 'strength', 'cardio', 'hiit'])
                ->description('The type of sport/activity for this workout'),
            'notes' => $schema->string()
                ->description('Detailed workout instructions, pace targets, intensity zones, exercise lists, sets/reps, or any other guidance. This field is critical for providing workout details since step builders are not available in v1.')
                ->optional(),
            'scheduled_at' => $schema->string()
                ->description('When the workout is scheduled in ISO 8601 format (YYYY-MM-DDTHH:MM:SS)'),
        ];
    }
}
