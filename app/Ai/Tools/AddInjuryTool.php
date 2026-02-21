<?php

namespace App\Ai\Tools;

use App\Enums\BodyPart;
use App\Enums\InjuryType;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class AddInjuryTool implements Tool
{
    public function description(): string
    {
        return <<<'TEXT'
        Add an injury record. Track current or past injuries that should be considered when creating workout plans.

        Injury Types: acute, chronic, recurring, post_surgery.
        Body Parts: shoulder, chest, biceps, triceps, forearm, wrist, hand, elbow, neck, upper_back, lower_back, core, ribs, hip, glutes, quadriceps, hamstring, knee, calf, ankle, foot, other.
        TEXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'injury_type' => $schema->string()->enum(InjuryType::class)->description('Type of injury.'),
            'body_part' => $schema->string()->enum(BodyPart::class)->description('Affected body part.'),
            'started_at' => $schema->string()->description('Date when the injury started (YYYY-MM-DD)'),
            'ended_at' => $schema->string()->description('Date when the injury was resolved (YYYY-MM-DD). Leave null if ongoing.')->nullable(),
            'notes' => $schema->string()->description('Optional notes about the injury')->nullable(),
        ];
    }

    public function handle(Request $request): string
    {
        $user = auth()->user();

        $injury = $user->injuries()->create([
            'injury_type' => InjuryType::from($request['injury_type']),
            'body_part' => BodyPart::from($request['body_part']),
            'started_at' => CarbonImmutable::parse($request['started_at']),
            'ended_at' => $request->has('ended_at') && $request['ended_at'] !== null
                ? CarbonImmutable::parse($request['ended_at'])
                : null,
            'notes' => $request['notes'] ?? null,
        ]);

        return json_encode([
            'success' => true,
            'injury' => [
                'id' => $injury->id,
                'injury_type' => $injury->injury_type->value,
                'body_part' => $injury->body_part->value,
                'started_at' => $injury->started_at->toDateString(),
                'ended_at' => $injury->ended_at?->toDateString(),
                'is_active' => $injury->is_active,
                'notes' => $injury->notes,
            ],
            'message' => 'Injury added successfully',
        ]);
    }
}
