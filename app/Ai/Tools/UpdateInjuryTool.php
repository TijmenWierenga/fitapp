<?php

namespace App\Ai\Tools;

use App\Enums\BodyPart;
use App\Enums\InjuryType;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class UpdateInjuryTool implements Tool
{
    public function description(): string
    {
        return 'Update an existing injury record. Use this to mark an injury as resolved (set ended_at), reopen it (set ended_at to null), or update details. Only provide the fields you want to change.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'injury_id' => $schema->integer()->description('The ID of the injury to update'),
            'injury_type' => $schema->string()->enum(InjuryType::class)->description('Type of injury.')->nullable(),
            'body_part' => $schema->string()->enum(BodyPart::class)->description('Affected body part.')->nullable(),
            'started_at' => $schema->string()->description('Date when the injury started (YYYY-MM-DD)')->nullable(),
            'ended_at' => $schema->string()->description('Date when the injury was resolved (YYYY-MM-DD). Set to null to reopen.')->nullable(),
            'notes' => $schema->string()->description('Notes about the injury.')->nullable(),
        ];
    }

    public function handle(Request $request): string
    {
        $user = auth()->user();
        $injury = $user->injuries()->find($request['injury_id']);

        if (! $injury) {
            return json_encode(['error' => 'Injury not found or access denied.']);
        }

        $updateData = [];

        if ($request->has('injury_type')) {
            $updateData['injury_type'] = InjuryType::from($request['injury_type']);
        }

        if ($request->has('body_part')) {
            $updateData['body_part'] = BodyPart::from($request['body_part']);
        }

        if ($request->has('started_at')) {
            $updateData['started_at'] = CarbonImmutable::parse($request['started_at']);
        }

        if ($request->has('ended_at')) {
            $updateData['ended_at'] = $request['ended_at'] !== null
                ? CarbonImmutable::parse($request['ended_at'])
                : null;
        }

        if ($request->has('notes')) {
            $updateData['notes'] = $request['notes'];
        }

        $effectiveStartedAt = $updateData['started_at'] ?? $injury->started_at;
        $effectiveEndedAt = array_key_exists('ended_at', $updateData) ? $updateData['ended_at'] : $injury->ended_at;

        if ($effectiveEndedAt !== null && $effectiveEndedAt < $effectiveStartedAt) {
            return json_encode(['error' => 'End date must be on or after the start date.']);
        }

        $injury->update($updateData);

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
            'message' => 'Injury updated successfully',
        ]);
    }
}
