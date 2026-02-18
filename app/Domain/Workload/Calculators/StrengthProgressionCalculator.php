<?php

declare(strict_types=1);

namespace App\Domain\Workload\Calculators;

use App\Domain\Workload\Results\StrengthProgressionResult;
use App\Domain\Workload\ValueObjects\DateRange;
use App\Domain\Workload\ValueObjects\StrengthRecord;

class StrengthProgressionCalculator
{
    /**
     * @param  array<StrengthRecord>  $records
     * @return array<StrengthProgressionResult>
     */
    public function calculate(array $records, DateRange $currentPeriod, DateRange $previousPeriod): array
    {
        /** @var array<int, array{name: string, current: array<StrengthRecord>, previous: array<StrengthRecord>}> $grouped */
        $grouped = [];

        foreach ($records as $record) {
            if (! isset($grouped[$record->exerciseId])) {
                $grouped[$record->exerciseId] = [
                    'name' => $record->exerciseName,
                    'current' => [],
                    'previous' => [],
                ];
            }

            if ($currentPeriod->contains($record->performedAt)) {
                $grouped[$record->exerciseId]['current'][] = $record;
            } elseif ($previousPeriod->contains($record->performedAt)) {
                $grouped[$record->exerciseId]['previous'][] = $record;
            }
        }

        $results = [];

        foreach ($grouped as $exerciseId => $data) {
            if (count($data['current']) === 0) {
                continue;
            }

            $currentBest = $this->bestE1RM($data['current']);
            $previousBest = count($data['previous']) > 0 ? $this->bestE1RM($data['previous']) : null;

            $changePct = $previousBest !== null && $previousBest > 0
                ? round(($currentBest - $previousBest) / $previousBest * 100, 1)
                : null;

            $results[] = new StrengthProgressionResult(
                exerciseId: $exerciseId,
                exerciseName: $data['name'],
                currentE1RM: round($currentBest, 1),
                previousE1RM: $previousBest !== null ? round($previousBest, 1) : null,
                changePct: $changePct,
            );
        }

        return $results;
    }

    /**
     * @param  array<StrengthRecord>  $records
     */
    private function bestE1RM(array $records): float
    {
        return max(array_map(
            fn (StrengthRecord $r): float => $r->estimated1RM(),
            $records,
        ));
    }
}
