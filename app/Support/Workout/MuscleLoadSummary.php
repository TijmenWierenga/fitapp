<?php

declare(strict_types=1);

namespace App\Support\Workout;

use App\Enums\Workout\MuscleGroup;

/**
 * Value object representing aggregated muscle load data for a workout.
 *
 * @property array<string, array{total: float, sources: array<int, array{description: string, load: float}>}> $loads
 */
class MuscleLoadSummary
{
    /**
     * @param  array<string, array{total: float, sources: array<int, array{description: string, load: float}>}>  $loads
     */
    public function __construct(private array $loads) {}

    /**
     * Get all muscle load data.
     *
     * @return array<string, array{total: float, sources: array<int, array{description: string, load: float}>}>
     */
    public function all(): array
    {
        return $this->loads;
    }

    /**
     * Get load data for a specific muscle group.
     *
     * @return array{total: float, sources: array<int, array{description: string, load: float}>}
     */
    public function forMuscle(MuscleGroup $muscle): array
    {
        return $this->loads[$muscle->value] ?? [
            'total' => 0.0,
            'sources' => [],
        ];
    }

    /**
     * Calculate total load across all muscle groups.
     */
    public function totalLoad(): float
    {
        return array_sum(array_map(
            fn (array $data): float => $data['total'],
            $this->loads
        ));
    }

    /**
     * Convert to array format suitable for creating WorkoutMuscleLoadSnapshot records.
     *
     * @return array<int, array{muscle_group: MuscleGroup, total_load: float, source_breakdown: array<int, array{description: string, load: float}>}>
     */
    public function toSnapshotData(): array
    {
        return collect($this->loads)
            ->map(fn (array $data, string $muscleValue): array => [
                'muscle_group' => MuscleGroup::from($muscleValue),
                'total_load' => $data['total'],
                'source_breakdown' => $data['sources'],
            ])
            ->values()
            ->all();
    }
}
