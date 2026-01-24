<?php

namespace App\Mcp\Tools;

use App\Enums\Workout\Sport;
use App\Models\Workout;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Validator;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class UpdateWorkoutTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Updates an existing workout. Only workouts that haven't been completed can be edited. You can update the name, sport, notes, or scheduled time.
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
            'workout_id' => ['required', 'integer', 'exists:workouts,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'sport' => ['sometimes', 'string', 'in:running,strength,cardio,hiit'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'scheduled_at' => ['sometimes', 'date_format:Y-m-d\TH:i:s'],
        ]);

        if ($validator->fails()) {
            return Response::error('Validation failed: '.json_encode($validator->errors()->toArray()));
        }

        $validated = $validator->validated();

        $workout = Workout::find($validated['workout_id']);

        if ($workout->user_id !== $user->id) {
            return Response::error('You do not have permission to update this workout.');
        }

        if (! $workout->canBeEdited()) {
            return Response::error('This workout cannot be edited because it has been completed.');
        }

        $updateData = [];

        if (isset($validated['name'])) {
            $updateData['name'] = $validated['name'];
        }

        if (isset($validated['sport'])) {
            $updateData['sport'] = Sport::from($validated['sport']);
        }

        if (array_key_exists('notes', $validated)) {
            $updateData['notes'] = $validated['notes'];
        }

        if (isset($validated['scheduled_at'])) {
            $updateData['scheduled_at'] = $validated['scheduled_at'];
        }

        $workout->update($updateData);

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
            'workout_id' => $schema->integer()
                ->description('The ID of the workout to update'),
            'name' => $schema->string()
                ->description('The new name/title of the workout')
                ->optional(),
            'sport' => $schema->enum(['running', 'strength', 'cardio', 'hiit'])
                ->description('The new type of sport/activity')
                ->optional(),
            'notes' => $schema->string()
                ->description('Updated workout instructions, pace targets, intensity zones, exercise lists, or guidance')
                ->optional(),
            'scheduled_at' => $schema->string()
                ->description('Updated scheduled time in ISO 8601 format (YYYY-MM-DDTHH:MM:SS)')
                ->optional(),
        ];
    }
}
