<?php

declare(strict_types=1);

namespace App\Actions\Garmin;

use App\Enums\Workout\BlockType;
use App\Models\Section;
use App\Models\Workout;
use App\Support\Fit\BlockMapper\AmrapMapper;
use App\Support\Fit\BlockMapper\BlockFitMapper;
use App\Support\Fit\BlockMapper\CircuitMapper;
use App\Support\Fit\BlockMapper\DistanceDurationMapper;
use App\Support\Fit\BlockMapper\EmomMapper;
use App\Support\Fit\BlockMapper\FitStepBuilder;
use App\Support\Fit\BlockMapper\ForTimeMapper;
use App\Support\Fit\BlockMapper\IntervalMapper;
use App\Support\Fit\BlockMapper\RestMapper;
use App\Support\Fit\BlockMapper\StraightSetsMapper;
use App\Support\Fit\BlockMapper\SupersetMapper;
use App\Support\Fit\FitMessage;
use App\Support\Fit\FitMessageFactory;

class WorkoutFitMapper
{
    /**
     * @return list<FitMessage>
     */
    public function map(Workout $workout): array
    {
        $builder = new FitStepBuilder;

        foreach ($workout->sections as $section) {
            $intensity = $this->sectionIntensity($section);

            foreach ($section->blocks as $block) {
                $this->resolveMapper($block->block_type)->map($block, $intensity, $builder);
            }
        }

        $sportMapping = SportMapper::fromActivity($workout->activity);

        return [
            FitMessageFactory::fileId(),
            FitMessageFactory::workout(
                name: $workout->name,
                sport: $sportMapping->sport,
                subSport: $sportMapping->subSport,
                numSteps: $builder->getStepCount(),
            ),
            ...$builder->getSteps(),
            ...$builder->buildExerciseTitleMessages(),
        ];
    }

    private function sectionIntensity(Section $section): int
    {
        $name = strtolower($section->name);

        if (str_contains($name, 'warm')) {
            return 2; // WARMUP
        }

        if (str_contains($name, 'cool')) {
            return 3; // COOLDOWN
        }

        return 0; // ACTIVE
    }

    private function resolveMapper(BlockType $blockType): BlockFitMapper
    {
        return match ($blockType) {
            BlockType::Rest => new RestMapper,
            BlockType::StraightSets => new StraightSetsMapper,
            BlockType::Circuit => new CircuitMapper,
            BlockType::Superset => new SupersetMapper,
            BlockType::Interval => new IntervalMapper,
            BlockType::Amrap => new AmrapMapper,
            BlockType::ForTime => new ForTimeMapper,
            BlockType::Emom => new EmomMapper,
            BlockType::DistanceDuration => new DistanceDurationMapper,
        };
    }
}
