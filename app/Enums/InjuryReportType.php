<?php

declare(strict_types=1);

namespace App\Enums;

enum InjuryReportType: string
{
    case SelfReporting = 'self_reporting';
    case PtVisit = 'pt_visit';
    case Milestone = 'milestone';

    public function label(): string
    {
        return match ($this) {
            self::SelfReporting => 'Self Report',
            self::PtVisit => 'PT Visit',
            self::Milestone => 'Milestone',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::SelfReporting => 'Personal status update from the patient',
            self::PtVisit => 'Summary from a physiotherapy session',
            self::Milestone => 'Recovery milestone',
        };
    }
}
