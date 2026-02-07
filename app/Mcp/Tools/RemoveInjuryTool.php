<?php

namespace App\Mcp\Tools;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Gate;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive]
class RemoveInjuryTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Remove an injury record from a user's profile. Use this when an injury
        record is no longer needed or was added in error.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'injury_id' => 'required|integer|exists:injuries,id',
        ], [
            'injury_id.exists' => 'Injury not found. Please provide a valid injury ID.',
        ]);

        $user = $request->user();

        $injury = $user->injuries()->find($validated['injury_id']);

        if (! $injury) {
            return Response::error('Injury not found or does not belong to this user.');
        }

        try {
            Gate::forUser($user)->authorize('delete', $injury);
        } catch (AuthorizationException) {
            return Response::error('You are not authorized to remove this injury.');
        }

        $injuryData = [
            'id' => $injury->id,
            'body_part' => $injury->body_part->label(),
            'injury_type' => $injury->injury_type->label(),
        ];

        $injury->delete();

        return Response::text(json_encode([
            'success' => true,
            'removed_injury' => $injuryData,
            'message' => 'Injury removed successfully',
        ]));
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'injury_id' => $schema->integer()->description('The ID of the injury to remove'),
        ];
    }

    /**
     * Get the tool's output schema.
     */
    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'success' => $schema->boolean()->required(),
            'removed_injury' => $schema->object([
                'id' => $schema->integer()->required(),
                'body_part' => $schema->string()->required(),
                'injury_type' => $schema->string()->required(),
            ])->required(),
            'message' => $schema->string()->required(),
        ];
    }
}
