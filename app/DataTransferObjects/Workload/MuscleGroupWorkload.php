<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Workload;

use App\Enums\WorkloadZone;

readonly class MuscleGroupWorkload
{
    public function __construct(
        public string $muscleGroupName,
        public string $muscleGroupLabel,
        public string $bodyPart,
        public float $acuteLoad,
        public float $chronicLoad,
        public float $acwr,
        public WorkloadZone $zone,
    ) {}

    public static function fromLoad(
        string $muscleGroupName,
        string $muscleGroupLabel,
        string $bodyPart,
        float $acuteLoad,
        float $chronicLoad,
    ): self {
        $acwr = $chronicLoad > 0
            ? round($acuteLoad / $chronicLoad, 2)
            : 0.0;

        return new self(
            muscleGroupName: $muscleGroupName,
            muscleGroupLabel: $muscleGroupLabel,
            bodyPart: $bodyPart,
            acuteLoad: $acuteLoad,
            chronicLoad: $chronicLoad,
            acwr: $acwr,
            zone: WorkloadZone::fromAcwr($acwr),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'muscle_group' => $this->muscleGroupName,
            'label' => $this->muscleGroupLabel,
            'body_part' => $this->bodyPart,
            'acute_load' => round($this->acuteLoad, 2),
            'chronic_load' => round($this->chronicLoad, 2),
            'acwr' => $this->acwr,
            'zone' => $this->zone->value,
            'zone_color' => $this->zone->color(),
        ];
    }
}
