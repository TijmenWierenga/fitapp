<?php

namespace App\Mcp\Tools;

use App\Enums\InjuryReportType;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;

class AddInjuryReportTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Add a report to an injury record. Use this to track progress, PT visits, or milestones.

        **Report Types:**
        - `self_reporting` - Personal status update from the patient
        - `pt_visit` - Summary from a physiotherapy session
        - `milestone` - Recovery milestone
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'injury_id' => 'required|integer',
            'type' => ['required', Rule::enum(InjuryReportType::class)],
            'content' => 'required|string|max:10000',
            'reported_at' => 'sometimes|date',
        ], [
            'type.enum' => 'Invalid report type. Must be one of: self_reporting, pt_visit, milestone.',
        ]);

        $user = $request->user();

        $injury = $user->injuries()->find($validated['injury_id']);

        if (! $injury) {
            return Response::error('Injury not found or access denied.');
        }

        $report = $injury->injuryReports()->create([
            'user_id' => $user->id,
            'type' => InjuryReportType::from($validated['type']),
            'content' => $validated['content'],
            'reported_at' => isset($validated['reported_at']) ? CarbonImmutable::parse($validated['reported_at']) : CarbonImmutable::today(),
        ]);

        return Response::structured([
            'success' => true,
            'report' => [
                'id' => $report->id,
                'injury_id' => $report->injury_id,
                'type' => $report->type->value,
                'type_label' => $report->type->label(),
                'content' => $report->content,
                'reported_at' => $report->reported_at->toDateString(),
                'created_at' => $report->created_at->toIso8601String(),
            ],
            'message' => 'Injury report added successfully',
        ]);
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'injury_id' => $schema->integer()->description('The ID of the injury to add a report to'),
            'type' => $schema->string()->description('Report type: self_reporting, pt_visit, or milestone'),
            'content' => $schema->string()->description('The report content (supports Markdown)'),
            'reported_at' => $schema->string()->description('The date the report is about (YYYY-MM-DD). Defaults to today.')->nullable(),
        ];
    }
}
