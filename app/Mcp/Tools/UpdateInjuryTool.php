<?php

namespace App\Mcp\Tools;

use App\Tools\Handlers\UpdateInjuryHandler;
use App\Tools\Input\UpdateInjuryInput;
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
    public function __construct(
        private UpdateInjuryHandler $handler,
    ) {}

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
            'injury_type' => ['sometimes', Rule::enum(\App\Enums\InjuryType::class)],
            'body_part' => ['sometimes', Rule::enum(\App\Enums\BodyPart::class)],
            'started_at' => 'sometimes|date',
            'ended_at' => 'sometimes|nullable|date',
            'notes' => 'sometimes|nullable|string|max:5000',
        ], [
            'injury_type.enum' => 'Invalid injury type. Must be one of: acute, chronic, recurring, post_surgery.',
            'body_part.enum' => 'Invalid body part.',
        ]);

        $result = $this->handler->execute(
            $request->user(),
            UpdateInjuryInput::fromArray($validated),
        );

        return $result->failed()
            ? Response::error($result->errorMessage())
            : Response::structured($result->toArray());
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return $this->handler->schema($schema);
    }
}
