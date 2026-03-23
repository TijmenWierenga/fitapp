<?php

declare(strict_types=1);

namespace App\Support\Fit\Decode;

readonly class FitMessageDefinition
{
    /**
     * @param  list<FitFieldDefinition>  $fieldDefinitions
     */
    public function __construct(
        public int $globalMessageNumber,
        public bool $bigEndian,
        public array $fieldDefinitions,
    ) {}

    public function dataSize(): int
    {
        $size = 0;

        foreach ($this->fieldDefinitions as $fieldDef) {
            $size += $fieldDef->size;
        }

        return $size;
    }
}
