<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Workout;

readonly class PainScore
{
    public function __construct(
        public int $injuryId,
        public int $painScore,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            injuryId: $data['injury_id'],
            painScore: $data['pain_score'],
        );
    }
}
