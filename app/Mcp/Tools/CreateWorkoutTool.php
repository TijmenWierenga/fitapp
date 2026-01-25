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

class CreateWorkoutTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Create a new workout with a scheduled date and time. The workout will be created for the specified user.

        Sports available: running, strength, cardio, hiit

        Dates/times should be in the user's local timezone and will be converted to UTC for storage.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'name' => 'required|string|max:255',
            'sport' => ['required', Rule::enum(Sport::class)],
            'scheduled_at' => 'required|date',
            'notes' => 'nullable|string|max:5000',
        ], [
            'user_id.exists' => 'User not found. Please provide a valid user ID.',
            'sport.Enum' => 'Invalid sport. Must be one of: running, strength, cardio, hiit.',
            'scheduled_at.date' => 'Please provide a valid date and time.',
        ]);

        $user = User::findOrFail($validated['user_id']);

        $scheduledAt = Carbon::parse($validated['scheduled_at'], $user->getTimezoneObject())->utc();

        $workout = Workout::create([
            'user_id' => $user->id,
            'name' => $validated['name'],
            'sport' => $validated['sport'],
            'scheduled_at' => $scheduledAt,
            'notes' => $validated['notes'] ?? null,
        ]);

        return Response::text(json_encode([
            'success' => true,
            'workout' => [
                'id' => $workout->id,
                'name' => $workout->name,
                'sport' => $workout->sport->value,
                'scheduled_at' => $user->toUserTimezone($workout->scheduled_at)->toIso8601String(),
                'notes' => $workout->notes,
                'completed' => false,
            ],
            'message' => 'Workout created successfully',
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
            'user_id' => $schema->integer()->description('The ID of the user creating the workout'),
            'name' => $schema->string()->description('The name/title of the workout (e.g., "Morning Run", "Leg Day")'),
            'sport' => $schema->string()->description('The sport type: running, strength, cardio, or hiit'),
            'scheduled_at' => $schema->string()->description('The date and time when the workout is scheduled (in user\'s timezone)'),
            'notes' => $schema->string()->description('Optional notes or description for the workout')->nullable(),
        ];
    }
}
