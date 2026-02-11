<?php

namespace App\Mcp\Tools;

use App\Models\Injury;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class GetInjuriesTool extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Get the authenticated user's injuries including active and past injuries with body parts, injury types, dates, notes, and recent reports.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $user = $request->user();
        $user->load(['injuries.injuryReports' => fn ($query) => $query->latest()->limit(3)]);

        $activeInjuries = $user->injuries->filter(fn (Injury $injury): bool => $injury->is_active);
        $resolvedInjuries = $user->injuries->filter(fn (Injury $injury): bool => ! $injury->is_active);

        $content = "# Injuries & Limitations\n\n";

        if ($activeInjuries->isEmpty() && $resolvedInjuries->isEmpty()) {
            return Response::text($content."*No injuries recorded.*\n\nUse the `add-injury` tool to track current or past injuries.\n");
        }

        if ($activeInjuries->isNotEmpty()) {
            $content .= "## Active Injuries\n\n";
            foreach ($activeInjuries as $injury) {
                $content .= $this->formatInjury($injury);
            }
        }

        if ($resolvedInjuries->isNotEmpty()) {
            $content .= "## Past Injuries\n\n";
            foreach ($resolvedInjuries as $injury) {
                $content .= $this->formatInjury($injury);
            }
        }

        return Response::text($content);
    }

    protected function formatInjury(Injury $injury): string
    {
        $endDate = $injury->ended_at ? " - {$injury->ended_at->toDateString()}" : ' - Present';
        $notes = $injury->notes ? " ({$injury->notes})" : '';

        $line = "- **{$injury->body_part->label()}** [{$injury->injury_type->label()}]: {$injury->started_at->toDateString()}{$endDate}{$notes}\n";

        if ($injury->injuryReports->isNotEmpty()) {
            foreach ($injury->injuryReports as $report) {
                $line .= "  - [{$report->type->label()}] {$report->reported_at->toDateString()}: {$report->content}\n";
            }
        }

        return $line;
    }
}
