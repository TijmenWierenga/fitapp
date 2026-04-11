<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Fit;

readonly class ExerciseGroup
{
    /**
     * @param  list<ParsedSet>  $sets
     */
    public function __construct(
        public string $key,
        public int $category,
        public int $name,
        public array $sets,
    ) {}
}
