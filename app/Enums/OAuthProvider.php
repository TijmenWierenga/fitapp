<?php

declare(strict_types=1);

namespace App\Enums;

enum OAuthProvider: string
{
    case Strava = 'strava';

    public function label(): string
    {
        return match ($this) {
            self::Strava => 'Strava',
        };
    }

    /**
     * @return list<string>
     */
    public function defaultScopes(): array
    {
        return match ($this) {
            self::Strava => ['read'],
        };
    }
}
