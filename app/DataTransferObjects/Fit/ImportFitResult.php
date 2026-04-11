<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Fit;

readonly class ImportFitResult
{
    /**
     * @param  list<string>  $warnings
     */
    public function __construct(
        public array $warnings,
    ) {}
}
