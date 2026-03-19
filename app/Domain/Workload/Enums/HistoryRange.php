<?php

declare(strict_types=1);

namespace App\Domain\Workload\Enums;

enum HistoryRange: string
{
    case ThreeMonths = '3m';
    case SixMonths = '6m';
    case OneYear = '1y';
    case AllTime = 'all';

    public function days(): ?int
    {
        return match ($this) {
            self::ThreeMonths => 90,
            self::SixMonths => 180,
            self::OneYear => 365,
            self::AllTime => null,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::ThreeMonths => '3M',
            self::SixMonths => '6M',
            self::OneYear => '1Y',
            self::AllTime => 'All',
        };
    }
}
