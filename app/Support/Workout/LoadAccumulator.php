<?php

declare(strict_types=1);

namespace App\Support\Workout;

use App\DataTransferObjects\Workload\MuscleGroupWorkload;
use App\Models\MuscleGroup;
use Illuminate\Support\Collection;

class LoadAccumulator
{
    /** @var array<int, array{acute: float, chronic: float}> */
    private array $loads = [];

    private int $unlinkedCount = 0;

    /**
     * @param  Collection<int, MuscleGroup>  $muscleGroups
     */
    public function __construct(
        private readonly Collection $muscleGroups,
    ) {
        foreach ($muscleGroups as $mg) {
            $this->loads[$mg->id] = ['acute' => 0.0, 'chronic' => 0.0];
        }
    }

    public function addLoad(int $muscleGroupId, float $load, bool $isAcute): void
    {
        $this->loads[$muscleGroupId]['chronic'] += $load;

        if ($isAcute) {
            $this->loads[$muscleGroupId]['acute'] += $load;
        }
    }

    public function recordUnlinked(): void
    {
        $this->unlinkedCount++;
    }

    public function unlinkedCount(): int
    {
        return $this->unlinkedCount;
    }

    /**
     * @return Collection<int, MuscleGroupWorkload>
     */
    public function toMuscleGroupWorkloads(int $chronicWeeks): Collection
    {
        return $this->muscleGroups
            ->map(fn (MuscleGroup $mg): MuscleGroupWorkload => new MuscleGroupWorkload(
                muscleGroupName: $mg->name,
                muscleGroupLabel: $mg->label,
                bodyPart: $mg->body_part->value,
                acuteLoad: $this->loads[$mg->id]['acute'],
                chronicLoad: $this->loads[$mg->id]['chronic'] / $chronicWeeks,
            ))
            ->filter(fn (MuscleGroupWorkload $w): bool => $w->acuteLoad > 0 || $w->chronicLoad > 0)
            ->values();
    }
}
