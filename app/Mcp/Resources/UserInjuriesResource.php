<?php

namespace App\Mcp\Resources;

use App\Models\Injury;
use App\Models\User;
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
        $user->load('injuries');

        $content = $this->buildInjuriesContent($user);

        return Response::text($content);
    }

    protected function buildInjuriesContent(User $user): string
    {
        $activeInjuries = $user->injuries->filter(fn (Injury $injury) => $injury->is_active);
        $resolvedInjuries = $user->injuries->filter(fn (Injury $injury) => ! $injury->is_active);

        $content = "# Injuries & Limitations\n\n";

        if ($activeInjuries->isEmpty() && $resolvedInjuries->isEmpty()) {
            return $content."*No injuries recorded.*\n\nUse the `add-injury` tool to track current or past injuries.\n";
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

        return $content;
    }

    protected function formatInjury(Injury $injury): string
    {
        $status = $injury->is_active ? 'Active' : 'Resolved';
        $endDate = $injury->ended_at ? " - {$injury->ended_at->toDateString()}" : ' - Present';
        $notes = $injury->notes ? " ({$injury->notes})" : '';

        return "- **{$injury->body_part->label()}** [{$injury->injury_type->label()}]: {$injury->started_at->toDateString()}{$endDate}{$notes}\n";
    }
}
