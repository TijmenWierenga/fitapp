<?php

declare(strict_types=1);

namespace App\Domain\Workload\Calculators;

use App\Domain\Workload\Results\SessionLoadResult;
use App\Domain\Workload\Results\WeekSummary;
use App\Domain\Workload\ValueObjects\CompletedSession;
use App\Domain\Workload\ValueObjects\DateRange;

class SessionLoadCalculator
{
    private const float WEEK_OVER_WEEK_WARNING_THRESHOLD = 15.0;

    private const float MONOTONY_WARNING_THRESHOLD = 2.0;

    /**
     * @param  array<CompletedSession>  $sessions
     * @param  array<DateRange>  $previousWeeks
     */
    public function calculate(array $sessions, DateRange $currentWeek, array $previousWeeks): SessionLoadResult
    {
        $currentSessions = array_filter(
            $sessions,
            fn (CompletedSession $s): bool => $currentWeek->contains($s->completedAt),
        );

        $currentWeeklyTotal = array_sum(array_map(
            fn (CompletedSession $s): int => $s->sessionLoad(),
            $currentSessions,
        ));

        $currentSessionCount = count($currentSessions);

        [$monotony, $strain] = $this->calculateMonotonyAndStrain($currentSessions, $currentWeek);

        $previousWeekSummaries = $this->buildPreviousWeekSummaries($sessions, $previousWeeks);

        $previousWeekTotal = $previousWeekSummaries[0]->totalLoad ?? 0;
        $weekOverWeekChangePct = $previousWeekTotal > 0
            ? round(($currentWeeklyTotal - $previousWeekTotal) / $previousWeekTotal * 100, 1)
            : 0.0;

        return new SessionLoadResult(
            currentWeeklyTotal: $currentWeeklyTotal,
            currentSessionCount: $currentSessionCount,
            monotony: $monotony,
            strain: $strain,
            previousWeeks: $previousWeekSummaries,
            weekOverWeekChangePct: $weekOverWeekChangePct,
            weekOverWeekWarning: abs($weekOverWeekChangePct) > self::WEEK_OVER_WEEK_WARNING_THRESHOLD,
            monotonyWarning: $monotony > self::MONOTONY_WARNING_THRESHOLD,
        );
    }

    /**
     * @param  array<CompletedSession>  $sessions
     * @return array{float, float}
     */
    private function calculateMonotonyAndStrain(array $sessions, DateRange $currentWeek): array
    {
        $dailyLoads = array_fill(0, 7, 0);

        foreach ($sessions as $session) {
            $dayIndex = (int) $currentWeek->from->diff($session->completedAt)->days;

            if ($dayIndex >= 0 && $dayIndex < 7) {
                $dailyLoads[$dayIndex] += $session->sessionLoad();
            }
        }

        $mean = array_sum($dailyLoads) / 7;

        if ($mean === 0.0) {
            return [0.0, 0.0];
        }

        $squaredDiffs = array_map(fn (int $load): float => ($load - $mean) ** 2, $dailyLoads);
        $stddev = sqrt(array_sum($squaredDiffs) / 7);

        if ($stddev === 0.0) {
            return [0.0, 0.0];
        }

        $monotony = round($mean / $stddev, 2);
        $strain = round(array_sum($dailyLoads) * $monotony, 1);

        return [$monotony, $strain];
    }

    /**
     * @param  array<CompletedSession>  $sessions
     * @param  array<DateRange>  $previousWeeks
     * @return array<WeekSummary>
     */
    private function buildPreviousWeekSummaries(array $sessions, array $previousWeeks): array
    {
        $summaries = [];

        foreach ($previousWeeks as $offset => $week) {
            $weekSessions = array_filter(
                $sessions,
                fn (CompletedSession $s): bool => $week->contains($s->completedAt),
            );

            $summaries[] = new WeekSummary(
                weekOffset: -($offset + 1),
                totalLoad: array_sum(array_map(
                    fn (CompletedSession $s): int => $s->sessionLoad(),
                    $weekSessions,
                )),
                sessionCount: count($weekSessions),
            );
        }

        return $summaries;
    }
}
