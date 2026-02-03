<?php

namespace App\Mcp\Tools;

use App\Models\Injury;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ListInjuriesTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        List the user's injuries with their IDs. Use this to get injury IDs before completing
        a workout with injury evaluations.

        **Filter options:**
        - `active` (default) - Only active/ongoing injuries
        - `resolved` - Only resolved/healed injuries
        - `all` - All injuries regardless of status
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'filter' => 'nullable|string|in:active,resolved,all',
        ]);

        $user = $request->user();
        $filter = $validated['filter'] ?? 'active';

        $query = $user->injuries()->orderBy('started_at', 'desc');

        match ($filter) {
            'active' => $query->active(),
            'resolved' => $query->resolved(),
            default => null,
        };

        $injuries = $query->get()->map(fn (Injury $injury): array => [
            'id' => $injury->id,
            'injury_type' => $injury->injury_type->value,
            'injury_type_label' => $injury->injury_type->label(),
            'body_part' => $injury->body_part->value,
            'body_part_label' => $injury->body_part->label(),
            'started_at' => $injury->started_at->toDateString(),
            'ended_at' => $injury->ended_at?->toDateString(),
            'is_active' => $injury->is_active,
            'notes' => $injury->notes,
        ]);

        return Response::text(json_encode([
            'injuries' => $injuries,
            'count' => $injuries->count(),
            'filter' => $filter,
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
            'filter' => $schema->string()->description('Filter injuries: active (default), resolved, or all')->nullable(),
        ];
    }
}
