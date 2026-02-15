<?php

namespace App\Actions;

use App\Models\Workout;
use App\Support\Fit\FitEncoder;
use App\Support\Fit\WorkoutFitMapper;

class ExportWorkoutFit
{
    public function __construct(
        private WorkoutFitMapper $mapper,
        private FitEncoder $encoder,
    ) {}

    public function execute(Workout $workout): string
    {
        $workout->loadMissing('sections.blocks.exercises.exerciseable');

        return $this->encoder->encode($this->mapper->map($workout));
    }
}
