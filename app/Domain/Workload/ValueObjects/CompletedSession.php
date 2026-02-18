<?php

declare(strict_types=1);

namespace App\Domain\Workload\ValueObjects;

use DateTimeImmutable;

readonly class CompletedSession
{
    public function __construct(
        public DateTimeImmutable $completedAt,
        public int $durationMinutes,
        public int $rpe,
    ) {}

    public function sessionLoad(): int
    {
        return $this->durationMinutes * $this->rpe;
    }
}
