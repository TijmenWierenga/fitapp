<?php

declare(strict_types=1);

namespace App\Tools\Input;

readonly class ListWorkoutsInput
{
    public function __construct(
        public string $filter,
        public int $limit,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            filter: $data['filter'] ?? 'all',
            limit: min($data['limit'] ?? 20, 100),
        );
    }
}
