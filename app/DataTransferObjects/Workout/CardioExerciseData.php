<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Workout;

readonly class CardioExerciseData
{
    public function __construct(
        public ?int $targetDuration = null,
        public ?float $targetDistance = null,
        public ?int $targetPaceMin = null,
        public ?int $targetPaceMax = null,
        public ?int $targetHeartRateZone = null,
        public ?int $targetHeartRateMin = null,
        public ?int $targetHeartRateMax = null,
        public ?int $targetPower = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            targetDuration: $data['target_duration'] ?? null,
            targetDistance: $data['target_distance'] ?? null,
            targetPaceMin: $data['target_pace_min'] ?? null,
            targetPaceMax: $data['target_pace_max'] ?? null,
            targetHeartRateZone: $data['target_heart_rate_zone'] ?? null,
            targetHeartRateMin: $data['target_heart_rate_min'] ?? null,
            targetHeartRateMax: $data['target_heart_rate_max'] ?? null,
            targetPower: $data['target_power'] ?? null,
        );
    }
}
