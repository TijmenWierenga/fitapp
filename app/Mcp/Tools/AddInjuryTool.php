<?php

namespace App\Mcp\Tools;

use App\Enums\BodyPart;
use App\Enums\InjuryType;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class AddInjuryTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Add an injury record for a user. Use this to track current or past injuries
        that should be considered when creating workout plans.

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
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'injury_type' => ['required', Rule::enum(InjuryType::class)],
            'body_part' => ['required', Rule::enum(BodyPart::class)],
            'started_at' => 'required|date',
            'ended_at' => 'nullable|date|after_or_equal:started_at',
            'notes' => 'nullable|string|max:5000',
        ], [
            'injury_type.Enum' => 'Invalid injury type. Must be one of: acute, chronic, recurring, post_surgery.',
            'body_part.Enum' => 'Invalid body part.',
            'ended_at.after_or_equal' => 'End date must be on or after the start date.',
        ]);

        $user = $request->user();

        $injury = $user->injuries()->create([
            'injury_type' => InjuryType::from($validated['injury_type']),
            'body_part' => BodyPart::from($validated['body_part']),
            'started_at' => CarbonImmutable::parse($validated['started_at']),
            'ended_at' => isset($validated['ended_at']) ? CarbonImmutable::parse($validated['ended_at']) : null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return Response::text(json_encode([
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
            'message' => 'Injury added successfully',
        ]));
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'injury_type' => $schema->string()->description('Type of injury: acute, chronic, recurring, or post_surgery'),
            'body_part' => $schema->string()->description('Affected body part (e.g., knee, shoulder, lower_back)'),
            'started_at' => $schema->string()->description('Date when the injury started (YYYY-MM-DD)'),
            'ended_at' => $schema->string()->description('Date when the injury was resolved (YYYY-MM-DD). Leave null if ongoing.')->nullable(),
            'notes' => $schema->string()->description('Optional notes about the injury')->nullable(),
        ];
    }

    /**
     * Get the tool's output schema.
     */
    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'success' => $schema->boolean()->required(),
            'injury' => $schema->object([
                'id' => $schema->integer()->required(),
                'injury_type' => $schema->string()->required(),
                'injury_type_label' => $schema->string()->required(),
                'body_part' => $schema->string()->required(),
                'body_part_label' => $schema->string()->required(),
                'body_part_region' => $schema->string()->required(),
                'started_at' => $schema->string()->required(),
                'ended_at' => $schema->string()->nullable(),
                'is_active' => $schema->boolean()->required(),
                'notes' => $schema->string()->nullable(),
            ])->required(),
            'message' => $schema->string()->required(),
        ];
    }
}
