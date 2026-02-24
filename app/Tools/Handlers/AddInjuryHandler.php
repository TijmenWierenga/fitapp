<?php

declare(strict_types=1);

namespace App\Tools\Handlers;

use App\Enums\BodyPart;
use App\Enums\InjuryType;
use App\Models\User;
use App\Tools\Input\AddInjuryInput;
use App\Tools\ToolResult;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class AddInjuryHandler
{
    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
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

    public function execute(User $user, AddInjuryInput $input): ToolResult
    {
        $injury = $user->injuries()->create([
            'injury_type' => InjuryType::from($input->injuryType),
            'body_part' => BodyPart::from($input->bodyPart),
            'started_at' => CarbonImmutable::parse($input->startedAt),
            'ended_at' => $input->endedAt !== null ? CarbonImmutable::parse($input->endedAt) : null,
            'notes' => $input->notes,
        ]);

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
            'message' => 'Injury added successfully',
        ]);
    }
}
