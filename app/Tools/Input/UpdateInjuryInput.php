<?php

declare(strict_types=1);

namespace App\Tools\Input;

readonly class UpdateInjuryInput
{
    /**
     * @param  array<string>  $providedFields
     */
    public function __construct(
        public int $injuryId,
        public ?string $injuryType,
        public ?string $bodyPart,
        public ?string $startedAt,
        public ?string $endedAt,
        public ?string $notes,
        private array $providedFields,
    ) {}

    public function has(string $field): bool
    {
        return in_array($field, $this->providedFields, true);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            injuryId: $data['injury_id'],
            injuryType: $data['injury_type'] ?? null,
            bodyPart: $data['body_part'] ?? null,
            startedAt: $data['started_at'] ?? null,
            endedAt: $data['ended_at'] ?? null,
            notes: $data['notes'] ?? null,
            providedFields: array_keys($data),
        );
    }
}
