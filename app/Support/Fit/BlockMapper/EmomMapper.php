<?php

declare(strict_types=1);

namespace App\Support\Fit\BlockMapper;

use App\Models\Block;

class EmomMapper implements BlockFitMapper
{
    public function map(Block $block, int $sectionIntensity, FitStepBuilder $builder): void
    {
        $startIndex = $builder->getStepIndex();
        $name = $block->exercises->first()?->name ?? 'Work';

        if ($block->work_interval && $block->work_interval > 0) {
            $builder->addTimeStep($name, $block->work_interval, $sectionIntensity);
        }

        if ($block->rounds && $block->rounds > 1) {
            $builder->addRepeatStep($startIndex, $block->rounds);
        }
    }
}
