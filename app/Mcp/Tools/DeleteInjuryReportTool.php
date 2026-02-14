<?php

namespace App\Mcp\Tools;

use App\Models\InjuryReport;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
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
    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'report_id' => 'required|integer',
        ]);

        $user = $request->user();

        $report = InjuryReport::with('injury')->find($validated['report_id']);

        if (! $report) {
            return Response::error('Injury report not found.');
        }

        if ($user->cannot('delete', $report)) {
            return Response::error('Cannot delete report. Access denied.');
        }

        $report->delete();

        return Response::structured([
            'success' => true,
            'message' => 'Injury report deleted successfully',
        ]);
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
