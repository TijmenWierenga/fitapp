<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Workout;

readonly class ExercisePresentation
{
    /**
     * @param  list<string>  $whatLines
     * @param  list<string>  $effortLines
     * @param  list<string>  $restLines
     */
    public function __construct(
        public string $dotColor,
        public string $typeLabel,
        public array $whatLines = [],
        public array $effortLines = [],
        public array $restLines = [],
    ) {}
}
