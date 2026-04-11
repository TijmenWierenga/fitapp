<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Fit;

use Livewire\Wireable;

readonly class FitActivityPreview implements Wireable
{
    public function __construct(
        public string $activity,
        public ?string $duration,
        public ?string $distance,
        public ?int $calories,
        public ?int $avgHeartRate,
        public ?int $maxHeartRate,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toLivewire(): array
    {
        return [
            'activity' => $this->activity,
            'duration' => $this->duration,
            'distance' => $this->distance,
            'calories' => $this->calories,
            'avgHeartRate' => $this->avgHeartRate,
            'maxHeartRate' => $this->maxHeartRate,
        ];
    }

    /**
     * @param  array<string, mixed>  $value
     */
    public static function fromLivewire(mixed $value): static
    {
        return new static(
            activity: $value['activity'],
            duration: $value['duration'],
            distance: $value['distance'],
            calories: $value['calories'],
            avgHeartRate: $value['avgHeartRate'],
            maxHeartRate: $value['maxHeartRate'],
        );
    }
}
