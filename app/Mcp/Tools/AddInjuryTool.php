<?php

namespace App\Mcp\Tools;

use App\Tools\Handlers\AddInjuryHandler;
use App\Tools\Input\AddInjuryInput;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;

class AddInjuryTool extends Tool
{
    public function __construct(
        private AddInjuryHandler $handler,
    ) {}

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
    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'injury_type' => ['required', Rule::enum(\App\Enums\InjuryType::class)],
            'body_part' => ['required', Rule::enum(\App\Enums\BodyPart::class)],
            'started_at' => 'required|date',
            'ended_at' => 'nullable|date|after_or_equal:started_at',
            'notes' => 'nullable|string|max:5000',
        ], [
            'injury_type.Enum' => 'Invalid injury type. Must be one of: acute, chronic, recurring, post_surgery.',
            'body_part.Enum' => 'Invalid body part.',
            'ended_at.after_or_equal' => 'End date must be on or after the start date.',
        ]);

        $result = $this->handler->execute(
            $request->user(),
            AddInjuryInput::fromArray($validated),
        );

        return Response::structured($result->toArray());
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return $this->handler->schema($schema);
    }
}
