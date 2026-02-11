<?php

namespace App\Mcp\Tools;

use App\Models\InjuryReport;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[IsIdempotent]
class UpdateInjuryReportTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = 'Update the content of an injury report. Only the original author can update a report.';

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'report_id' => 'required|integer',
            'content' => 'sometimes|string|max:10000',
            'reported_at' => 'sometimes|date',
        ]);

        $user = $request->user();

        $report = InjuryReport::find($validated['report_id']);

        if (! $report) {
            return Response::error('Injury report not found.');
        }

        if ($user->cannot('update', $report)) {
            return Response::error('You are not authorized to update this report.');
        }

        $updateData = array_filter([
            'content' => $validated['content'] ?? null,
            'reported_at' => isset($validated['reported_at']) ? \Carbon\CarbonImmutable::parse($validated['reported_at']) : null,
        ], fn ($value): bool => $value !== null);

        $report->update($updateData);

        return Response::text(json_encode([
            'success' => true,
            'report' => [
                'id' => $report->id,
                'injury_id' => $report->injury_id,
                'type' => $report->type->value,
                'type_label' => $report->type->label(),
                'content' => $report->content,
                'reported_at' => $report->reported_at->toDateString(),
                'created_at' => $report->created_at->toIso8601String(),
                'updated_at' => $report->updated_at->toIso8601String(),
            ],
            'message' => 'Injury report updated successfully',
        ]));
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'report_id' => $schema->integer()->description('The ID of the injury report to update'),
            'content' => $schema->string()->description('The updated report content')->nullable(),
            'reported_at' => $schema->string()->description('The date the report is about (YYYY-MM-DD)')->nullable(),
        ];
    }
}
