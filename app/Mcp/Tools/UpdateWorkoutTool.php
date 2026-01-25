<?php

namespace App\Mcp\Tools;

use App\Enums\Workout\Sport;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Carbon;
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

        You can update the name, sport, scheduled time, or notes. Only provide the fields you want to change.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'workout_id' => 'required|integer',
            'name' => 'sometimes|string|max:255',
            'sport' => ['sometimes', Rule::enum(Sport::class)],
            'scheduled_at' => 'sometimes|date',
            'notes' => 'nullable|string|max:5000',
        ], [
            'user_id.exists' => 'User not found. Please provide a valid user ID.',
            'sport.Enum' => 'Invalid sport. Must be one of: running, strength, cardio, hiit.',
            'scheduled_at.date' => 'Please provide a valid date and time.',
        ]);

        $user = User::findOrFail($validated['user_id']);

        $workout = Workout::where('user_id', $user->id)->find($validated['workout_id']);

        if (! $workout) {
            return Response::error('Workout not found or access denied');
        }

        if (! $workout->canBeEdited()) {
            return Response::error('Cannot update completed workouts');
        }

        $updateData = [];

        if (isset($validated['name'])) {
            $updateData['name'] = $validated['name'];
        }

        if (isset($validated['sport'])) {
            $updateData['sport'] = $validated['sport'];
        }

        if (isset($validated['scheduled_at'])) {
            $updateData['scheduled_at'] = Carbon::parse($validated['scheduled_at'], $user->getTimezoneObject())->utc();
        }

        if (array_key_exists('notes', $validated)) {
            $updateData['notes'] = $validated['notes'];
        }

        $workout->update($updateData);

        return Response::text(json_encode([
            'success' => true,
            'workout' => [
                'id' => $workout->id,
                'name' => $workout->name,
                'sport' => $workout->sport->value,
                'scheduled_at' => $user->toUserTimezone($workout->scheduled_at)->toIso8601String(),
                'notes' => $workout->notes,
                'completed' => $workout->isCompleted(),
            ],
            'message' => 'Workout updated successfully',
        ]));
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'user_id' => $schema->integer()->description('The ID of the user who owns the workout'),
            'workout_id' => $schema->integer()->description('The ID of the workout to update'),
            'name' => $schema->string()->description('The new name for the workout')->nullable(),
            'sport' => $schema->string()->description('The new sport type: running, strength, cardio, or hiit')->nullable(),
            'scheduled_at' => $schema->string()->description('The new scheduled date and time (in user\'s timezone)')->nullable(),
            'notes' => $schema->string()->description('The new notes for the workout')->nullable(),
        ];
    }
}
