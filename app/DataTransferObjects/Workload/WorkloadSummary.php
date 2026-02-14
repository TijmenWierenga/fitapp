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
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'muscle_groups' => $this->muscleGroups->map(fn (MuscleGroupWorkload $workload): array => $workload->toArray())->values()->toArray(),
            'active_injuries' => $this->activeInjuries->toArray(),
            'unlinked_exercise_count' => $this->unlinkedExerciseCount,
        ];
    }
}
