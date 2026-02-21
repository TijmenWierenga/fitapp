<?php

namespace App\Ai\Tools;

use App\Models\Injury;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class GetInjuriesTool implements Tool
{
    public function description(): string
    {
        return 'Get the user\'s injuries including active and past injuries with body parts, injury types, dates, and notes. Use before creating workout plans to avoid exercises that aggravate active injuries.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function handle(Request $request): string
    {
        $user = auth()->user();
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

        return json_encode([
            'count' => $data->count(),
            'injuries' => $data->toArray(),
        ]);
    }
}
