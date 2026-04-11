<?php

declare(strict_types=1);

namespace App\Support\Fit\Decode;

use App\Support\Fit\FitBaseType;

readonly class FitFieldDefinition
{
    public function __construct(
        public int $fieldNumber,
        public int $size,
        public FitBaseType $baseType,
    ) {}
}
