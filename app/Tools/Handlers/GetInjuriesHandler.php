<?php

declare(strict_types=1);

namespace App\Tools\Handlers;

use App\Models\Injury;
use App\Models\User;
use App\Tools\ToolResult;

class GetInjuriesHandler
{
    public function execute(User $user): ToolResult
    {
        $injuries = $user->injuries()
            ->with(['injuryReports' => fn ($query) => $query->latest()->limit(3)])
            ->get();

        $data = $injuries->map(fn (Injury $injury): array => [
            'id' => $injury->id,
            'injury_type' => $injury->injury_type->value,
            'injury_type_label' => $injury->injury_type->label(),
            'body_part' => $injury->body_part->value,
            'body_part_label' => $injury->body_part->label(),
            'started_at' => $injury->started_at->toDateString(),
            'ended_at' => $injury->ended_at?->toDateString(),
            'is_active' => $injury->is_active,
            'notes' => $injury->notes,
            'recent_reports' => $injury->injuryReports->map(fn ($report): array => [
                'type' => $report->type->value,
                'reported_at' => $report->reported_at->toDateString(),
                'content' => $report->content,
            ])->toArray(),
        ]);

        return ToolResult::success([
            'count' => $data->count(),
            'injuries' => $data->toArray(),
        ]);
    }
}
