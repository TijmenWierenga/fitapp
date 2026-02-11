<?php

namespace App\Mcp\Tools;

use App\Models\InjuryReport;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Gate;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[IsDestructive]
class DeleteInjuryReportTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = 'Delete an injury report. The report author or injury owner can delete a report.';

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'report_id' => 'required|integer',
        ]);

        $user = $request->user();

        $report = InjuryReport::with('injury')->find($validated['report_id']);

        if (! $report) {
            return Response::error('Injury report not found.');
        }

        try {
            Gate::forUser($user)->authorize('delete', $report);
        } catch (AuthorizationException) {
            return Response::error('You are not authorized to delete this report.');
        }

        $report->delete();

        return Response::text(json_encode([
            'success' => true,
            'message' => 'Injury report deleted successfully',
        ]));
    }

    /**
     * Get the tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'report_id' => $schema->integer()->description('The ID of the injury report to delete'),
        ];
    }
}
