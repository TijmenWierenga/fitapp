<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Workload;

readonly class MuscleGroupWorkload
{
    public float $acwr;

    public string $zone;

    public string $zoneColor;

    public function __construct(
        public string $muscleGroupName,
        public string $muscleGroupLabel,
        public string $bodyPart,
        public float $acuteLoad,
        public float $chronicLoad,
    ) {
        $this->acwr = $this->chronicLoad > 0
            ? round($this->acuteLoad / $this->chronicLoad, 2)
            : 0.0;
        $this->zone = self::determineZone($this->acwr);
        $this->zoneColor = self::zoneColor($this->zone);
    }

    private static function determineZone(float $acwr): string
    {
        if ($acwr === 0.0) {
            return 'inactive';
        }

        return match (true) {
            $acwr < 0.8 => 'undertraining',
            $acwr <= 1.3 => 'sweet_spot',
            $acwr <= 1.5 => 'caution',
            default => 'danger',
        };
    }

    private static function zoneColor(string $zone): string
    {
        return match ($zone) {
            'inactive', 'undertraining' => 'gray',
            'sweet_spot' => 'green',
            'caution' => 'yellow',
            'danger' => 'red',
        };
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
            'zone' => $this->zone,
            'zone_color' => $this->zoneColor,
        ];
    }
}
