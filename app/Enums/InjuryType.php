<?php

declare(strict_types=1);

namespace App\Enums;

enum InjuryType: string
{
    case Acute = 'acute';
    case Chronic = 'chronic';
    case Recurring = 'recurring';
    case PostSurgery = 'post_surgery';

    public function label(): string
    {
        return match ($this) {
            self::Acute => 'Acute',
            self::Chronic => 'Chronic',
            self::Recurring => 'Recurring',
            self::PostSurgery => 'Post-Surgery',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Acute => 'Recent injury requiring immediate attention',
            self::Chronic => 'Long-term condition that persists over time',
            self::Recurring => 'Injury that comes and goes periodically',
            self::PostSurgery => 'Recovery from a surgical procedure',
        };
    }
}
