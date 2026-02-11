<?php

namespace App\Mcp\Tools;

use App\Enums\InjuryReportType;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class ListInjuryReportsTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        List reports for an injury. Optionally filter by report type.

        **Report Types:**
        - `self_reporting` - Personal status update from the patient
        - `pt_visit` - Summary from a physiotherapy session
        - `milestone` - Recovery milestone
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'injury_id' => 'required|integer',
            'type' => ['sometimes', Rule::enum(InjuryReportType::class)],
            'limit' => 'sometimes|integer|min:1|max:100',
        ], [
            'type.enum' => 'Invalid report type. Must be one of: self_reporting, pt_visit, milestone.',
        ]);

        $user = $request->user();

        $injury = $user->injuries()->find($validated['injury_id']);

        if (! $injury) {
            return Response::error('Injury not found or does not belong to this user.');
        }

        $query = $injury->injuryReports()->with('user')->latest();

        if (isset($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        $limit = $validated['limit'] ?? 20;
        $reports = $query->limit($limit)->get();

        return Response::text(json_encode([
            'injury_id' => $injury->id,
            'total' => $reports->count(),
            'reports' => $reports->map(fn ($report): array => [
                'id' => $report->id,
                'type' => $report->type->value,
                'type_label' => $report->type->label(),
                'content' => $report->content,
                'reported_at' => $report->reported_at->toDateString(),
                'author' => $report->user->name,
                'created_at' => $report->created_at->toIso8601String(),
            ])->all(),
        ]));
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'injury_id' => $schema->integer()->description('The ID of the injury to list reports for'),
            'type' => $schema->string()->description('Filter by report type: self_reporting, pt_visit, or milestone')->nullable(),
            'limit' => $schema->integer()->description('Maximum number of reports to return (default: 20, max: 100)')->nullable(),
        ];
    }
}
