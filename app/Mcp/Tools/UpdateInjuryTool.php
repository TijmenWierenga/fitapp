<?php

namespace App\Mcp\Tools;

use App\Enums\BodyPart;
use App\Enums\InjuryType;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[IsIdempotent]
class UpdateInjuryTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Update an existing injury record. Use this to mark an injury as resolved (set ended_at),
        reopen an injury (set ended_at to null), or update any other injury details.

        Only provide the fields you want to change.

        **Injury Types:**
        - `acute` - Recent injury requiring immediate attention
        - `chronic` - Long-term condition that persists over time
        - `recurring` - Injury that comes and goes periodically
        - `post_surgery` - Recovery from a surgical procedure

        **Body Parts (by region):**
        - Upper Body: shoulder, chest, biceps, triceps, forearm, wrist, hand, elbow
        - Core/Spine: neck, upper_back, lower_back, core, ribs
        - Lower Body: hip, glutes, quadriceps, hamstring, knee, calf, ankle, foot
        - Other: other
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'injury_id' => 'required|integer',
            'injury_type' => ['sometimes', Rule::enum(InjuryType::class)],
            'body_part' => ['sometimes', Rule::enum(BodyPart::class)],
            'started_at' => 'sometimes|date',
            'ended_at' => 'sometimes|nullable|date',
            'notes' => 'sometimes|nullable|string|max:5000',
        ], [
            'injury_type.enum' => 'Invalid injury type. Must be one of: acute, chronic, recurring, post_surgery.',
            'body_part.enum' => 'Invalid body part.',
        ]);

        $user = $request->user();

        $injury = $user->injuries()->find($validated['injury_id']);

        if (! $injury) {
            return Response::error('Injury not found or access denied.');
        }

        $updateData = [];

        if (isset($validated['injury_type'])) {
            $updateData['injury_type'] = InjuryType::from($validated['injury_type']);
        }

        if (isset($validated['body_part'])) {
            $updateData['body_part'] = BodyPart::from($validated['body_part']);
        }

        if (isset($validated['started_at'])) {
            $updateData['started_at'] = CarbonImmutable::parse($validated['started_at']);
        }

        if (array_key_exists('ended_at', $validated)) {
            $updateData['ended_at'] = isset($validated['ended_at'])
                ? CarbonImmutable::parse($validated['ended_at'])
                : null;
        }

        if (array_key_exists('notes', $validated)) {
            $updateData['notes'] = $validated['notes'];
        }

        // Cross-field date validation
        $effectiveStartedAt = $updateData['started_at'] ?? $injury->started_at;
        $effectiveEndedAt = array_key_exists('ended_at', $updateData) ? $updateData['ended_at'] : $injury->ended_at;

        if ($effectiveEndedAt !== null && $effectiveEndedAt < $effectiveStartedAt) {
            return Response::error('End date must be on or after the start date.');
        }

        $injury->update($updateData);

        return Response::structured([
            'success' => true,
            'injury' => [
                'id' => $injury->id,
                'injury_type' => $injury->injury_type->value,
                'injury_type_label' => $injury->injury_type->label(),
                'body_part' => $injury->body_part->value,
                'body_part_label' => $injury->body_part->label(),
                'body_part_region' => $injury->body_part->region(),
                'started_at' => $injury->started_at->toDateString(),
                'ended_at' => $injury->ended_at?->toDateString(),
                'is_active' => $injury->is_active,
                'notes' => $injury->notes,
            ],
            'message' => 'Injury updated successfully',
        ]);
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'injury_id' => $schema->integer()->description('The ID of the injury to update'),
            'injury_type' => $schema->string()->description('Type of injury: acute, chronic, recurring, or post_surgery')->nullable(),
            'body_part' => $schema->string()->description('Affected body part (e.g., knee, shoulder, lower_back)')->nullable(),
            'started_at' => $schema->string()->description('Date when the injury started (YYYY-MM-DD)')->nullable(),
            'ended_at' => $schema->string()->description('Date when the injury was resolved (YYYY-MM-DD). Set to null to reopen.')->nullable(),
            'notes' => $schema->string()->description('Notes about the injury. Set to null to clear.')->nullable(),
        ];
    }
}
