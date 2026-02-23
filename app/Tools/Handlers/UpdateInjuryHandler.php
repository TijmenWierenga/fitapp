<?php

declare(strict_types=1);

namespace App\Tools\Handlers;

use App\Enums\BodyPart;
use App\Enums\InjuryType;
use App\Models\User;
use App\Tools\Input\UpdateInjuryInput;
use App\Tools\ToolResult;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class UpdateInjuryHandler
{
    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'injury_id' => $schema->integer()->description('The ID of the injury to update'),
            'injury_type' => $schema->string()->enum(InjuryType::class)->description('Type of injury.')->nullable(),
            'body_part' => $schema->string()->enum(BodyPart::class)->description('Affected body part.')->nullable(),
            'started_at' => $schema->string()->description('Date when the injury started (YYYY-MM-DD)')->nullable(),
            'ended_at' => $schema->string()->description('Date when the injury was resolved (YYYY-MM-DD). Set to null to reopen.')->nullable(),
            'notes' => $schema->string()->description('Notes about the injury. Set to null to clear.')->nullable(),
        ];
    }

    public function execute(User $user, UpdateInjuryInput $input): ToolResult
    {
        $injury = $user->injuries()->find($input->injuryId);

        if (! $injury) {
            return ToolResult::error('Injury not found or access denied.');
        }

        $updateData = [];

        if ($input->has('injury_type')) {
            $updateData['injury_type'] = InjuryType::from($input->injuryType);
        }

        if ($input->has('body_part')) {
            $updateData['body_part'] = BodyPart::from($input->bodyPart);
        }

        if ($input->has('started_at')) {
            $updateData['started_at'] = CarbonImmutable::parse($input->startedAt);
        }

        if ($input->has('ended_at')) {
            $updateData['ended_at'] = $input->endedAt !== null
                ? CarbonImmutable::parse($input->endedAt)
                : null;
        }

        if ($input->has('notes')) {
            $updateData['notes'] = $input->notes;
        }

        // Cross-field date validation
        $effectiveStartedAt = $updateData['started_at'] ?? $injury->started_at;
        $effectiveEndedAt = array_key_exists('ended_at', $updateData) ? $updateData['ended_at'] : $injury->ended_at;

        if ($effectiveEndedAt !== null && $effectiveEndedAt < $effectiveStartedAt) {
            return ToolResult::error('End date must be on or after the start date.');
        }

        $injury->update($updateData);

        return ToolResult::success([
            'injury' => [
                'id' => $injury->id,
                'injury_type' => $injury->injury_type->value,
                'injury_type_label' => $injury->injury_type->label(),
                'body_part' => $injury->body_part->value,
                'body_part_label' => $injury->body_part->label(),
                'body_part_region' => $injury->body_part->region(),
                'started_at' => $injury->started_at->toDateString(),
                'ended_at' => $injury->ended_at?->toDateString(),
                'is_active' => $injury->is_active,
                'notes' => $injury->notes,
            ],
            'message' => 'Injury updated successfully',
        ]);
    }
}
