<?php

declare(strict_types=1);

namespace App\Support\Workout;

readonly class Load
{
    public function __construct(
        public float $acute = 0.0,
        public float $chronic = 0.0,
    ) {}

    public static function zero(): self
    {
        return new self;
    }

    public function addVolume(float $volume, bool $isAcute): self
    {
        return new self(
            acute: $isAcute ? $this->acute + $volume : $this->acute,
            chronic: $this->chronic + $volume,
        );
    }
}
