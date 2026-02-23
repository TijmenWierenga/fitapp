<?php

namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class GetCurrentDateTimeTool implements Tool
{
    public function description(): string
    {
        return 'Get the current date, time, and day of the week in the user\'s timezone. Use this before planning or scheduling workouts.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function handle(Request $request): string
    {
        $now = auth()->user()->currentTimeInTimezone();

        return json_encode([
            'date_time' => $now->toIso8601String(),
            'date' => $now->format('Y-m-d'),
            'time' => $now->format('H:i'),
            'day_of_week' => $now->format('l'),
            'timezone' => $now->timezone->getName(),
        ]);
    }
}
