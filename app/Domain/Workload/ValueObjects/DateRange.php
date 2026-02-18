<?php

declare(strict_types=1);

namespace App\Domain\Workload\ValueObjects;

use DateTimeImmutable;

readonly class DateRange
{
    public function __construct(
        public DateTimeImmutable $from,
        public DateTimeImmutable $to,
    ) {}

    public function contains(DateTimeImmutable $date): bool
    {
        return $date >= $this->from && $date <= $this->to;
    }

    public function days(): int
    {
        return (int) $this->from->diff($this->to)->days;
    }
}
