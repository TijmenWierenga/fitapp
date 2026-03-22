<?php

declare(strict_types=1);

namespace App\Domain\Workload\Calculators;

use App\Domain\Workload\Enums\AcwrZone;
use App\Domain\Workload\Results\DailyEwmaPoint;
use App\Domain\Workload\Results\EwmaLoadResult;
use App\Domain\Workload\ValueObjects\CompletedSession;
use DateTimeImmutable;

class EwmaLoadCalculator
{
    private const float ACUTE_LAMBDA = 2 / (7 + 1);

    private const float CHRONIC_LAMBDA = 2 / (28 + 1);

    private const int CHART_DAYS = 42;

    /**
     * @param  array<CompletedSession>  $sessions
     */
    public function calculate(array $sessions, DateTimeImmutable $from, DateTimeImmutable $to): EwmaLoadResult
    {
        $dailyLoads = $this->buildDailyLoadMap($sessions, $from, $to);
        $totalSessions = count($sessions);

        $acuteLoad = 0.0;
        $chronicLoad = 0.0;
        $dailyPoints = [];

        $totalDays = (int) $from->diff($to)->days;
        $chartStart = max(0, $totalDays - self::CHART_DAYS);

        $currentDate = $from;

        for ($day = 0; $day <= $totalDays; $day++) {
            $dateKey = $currentDate->format('Y-m-d');
            $dayLoad = $dailyLoads[$dateKey] ?? 0;

            $acuteLoad = $dayLoad * self::ACUTE_LAMBDA + $acuteLoad * (1 - self::ACUTE_LAMBDA);
            $chronicLoad = $dayLoad * self::CHRONIC_LAMBDA + $chronicLoad * (1 - self::CHRONIC_LAMBDA);

            if ($day >= $chartStart) {
                $acwr = $chronicLoad > 0 ? round($acuteLoad / $chronicLoad, 2) : null;
                $tsb = round($chronicLoad - $acuteLoad, 1);

                $dailyPoints[] = new DailyEwmaPoint(
                    date: $dateKey,
                    acuteLoad: round($acuteLoad, 1),
                    chronicLoad: round($chronicLoad, 1),
                    acwr: $acwr,
                    tsb: $tsb,
                );
            }

            $currentDate = $currentDate->modify('+1 day');
        }

        $acwr = $chronicLoad > 0 ? round($acuteLoad / $chronicLoad, 2) : null;
        $tsb = round($chronicLoad - $acuteLoad, 1);

        return new EwmaLoadResult(
            currentAcuteLoad: round($acuteLoad, 1),
            currentChronicLoad: round($chronicLoad, 1),
            acwr: $acwr,
            tsb: $tsb,
            acwrZone: AcwrZone::fromAcwr($acwr),
            totalSessions: $totalSessions,
            dailyPoints: $dailyPoints,
        );
    }

    /**
     * @param  array<CompletedSession>  $sessions
     * @return array<string, int>
     */
    private function buildDailyLoadMap(array $sessions, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $map = [];

        foreach ($sessions as $session) {
            $dateKey = $session->completedAt->format('Y-m-d');
            $map[$dateKey] = ($map[$dateKey] ?? 0) + $session->sessionLoad();
        }

        return $map;
    }
}
