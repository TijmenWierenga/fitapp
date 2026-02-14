<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Workload;

use Illuminate\Support\Collection;

readonly class WorkloadSummary
{
    /**
     * @param  Collection<int, MuscleGroupWorkload>  $muscleGroups
     * @param  Collection<int, array<string, mixed>>  $activeInjuries
     */
    public function __construct(
        public Collection $muscleGroups,
        public Collection $activeInjuries,
        public int $unlinkedExerciseCount,
        public int $dataSpanDays,
    ) {}

    /**
     * @return Collection<int, string>
     */
    public function warnings(): Collection
    {
        $warnings = collect();

        if ($this->muscleGroups->isNotEmpty() && $this->dataSpanDays < 28) {
            $warnings->push("Based on {$this->dataSpanDays} of 28 days of data. ACWR values may not be reliable yet.");
        }

        if ($this->unlinkedExerciseCount > 0) {
            $warnings->push("{$this->unlinkedExerciseCount} exercise(s) in recent workouts are not linked to the exercise library and are excluded from workload tracking.");
        }

        return $warnings;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'muscle_groups' => $this->muscleGroups->map(fn (MuscleGroupWorkload $workload): array => $workload->toArray())->values()->toArray(),
            'active_injuries' => $this->activeInjuries->toArray(),
            'warnings' => $this->warnings()->values()->toArray(),
            'unlinked_exercise_count' => $this->unlinkedExerciseCount,
            'data_span_days' => $this->dataSpanDays,
        ];
    }
}
