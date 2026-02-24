<?php

declare(strict_types=1);

namespace App\Tools\Input;

readonly class AddInjuryInput
{
    public function __construct(
        public string $injuryType,
        public string $bodyPart,
        public string $startedAt,
        public ?string $endedAt,
        public ?string $notes,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            injuryType: $data['injury_type'],
            bodyPart: $data['body_part'],
            startedAt: $data['started_at'],
            endedAt: $data['ended_at'] ?? null,
            notes: $data['notes'] ?? null,
        );
    }
}
