<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Fit;

readonly class FitImportContext
{
    public function __construct(
        public ParsedActivity $parsedActivity,
        public string $rawBytes,
    ) {}
}
