<?php

namespace App\Mcp\Resources;

use App\Models\Injury;
use App\Models\User;
use App\Support\Markdown\MarkdownBuilder;
use Laravel\Mcp\Enums\Role;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Annotations\Audience;
use Laravel\Mcp\Server\Annotations\Priority;
use Laravel\Mcp\Server\Resource;

#[Audience(Role::Assistant)]
#[Priority(0.8)]
class UserInjuriesResource extends Resource
{
    /**
     * The resource URI.
     */
    protected string $uri = 'user://injuries';

    /**
     * The resource's description.
     */
    protected string $description = <<<'MARKDOWN'
        Read-only injury information including active and past injuries with body parts, injury types, dates, and notes.

        Use URI: user://injuries
    MARKDOWN;

    /**
     * Handle the resource request.
     */
    public function handle(Request $request): Response
    {
        $user = $request->user();
        $user->load(['injuries.injuryReports' => fn ($query) => $query->latest()->limit(3)]);

        $content = $this->buildInjuriesContent($user);

        return Response::text($content);
    }

    protected function buildInjuriesContent(User $user): string
    {
        $activeInjuries = $user->injuries->filter(fn (Injury $injury) => $injury->is_active);
        $resolvedInjuries = $user->injuries->filter(fn (Injury $injury) => ! $injury->is_active);

        if ($activeInjuries->isEmpty() && $resolvedInjuries->isEmpty()) {
            return MarkdownBuilder::make()
                ->heading('Injuries & Limitations')
                ->line('*No injuries recorded.*')
                ->blankLine()
                ->line('Use the `add-injury` tool to track current or past injuries.')
                ->toString();
        }

        return MarkdownBuilder::make()
            ->heading('Injuries & Limitations')
            ->when($activeInjuries->isNotEmpty(), fn (MarkdownBuilder $md) => $md
                ->heading('Active Injuries', 2)
                ->each($activeInjuries, fn (Injury $injury, MarkdownBuilder $md) => $this->formatInjury($injury, $md))
            )
            ->when($resolvedInjuries->isNotEmpty(), fn (MarkdownBuilder $md) => $md
                ->heading('Past Injuries', 2)
                ->each($resolvedInjuries, fn (Injury $injury, MarkdownBuilder $md) => $this->formatInjury($injury, $md))
            )
            ->toString();
    }

    protected function formatInjury(Injury $injury, MarkdownBuilder $md): void
    {
        $endDate = $injury->ended_at ? " - {$injury->ended_at->toDateString()}" : ' - Present';
        $notes = $injury->notes ? " ({$injury->notes})" : '';

        $md->listItem("**{$injury->body_part->label()}** [{$injury->injury_type->label()}]: {$injury->started_at->toDateString()}{$endDate}{$notes}");

        foreach ($injury->injuryReports as $report) {
            $md->listItem("[{$report->type->label()}] {$report->reported_at->toDateString()}: {$report->content}", 1);
        }
    }
}
