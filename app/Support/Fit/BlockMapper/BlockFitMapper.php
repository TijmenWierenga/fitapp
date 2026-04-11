<?php

declare(strict_types=1);

namespace App\Support\Fit\BlockMapper;

use App\Models\Block;

interface BlockFitMapper
{
    public function map(Block $block, int $sectionIntensity, FitStepBuilder $builder): void;
}
