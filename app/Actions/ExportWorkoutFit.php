<?php

namespace App\Actions;

use App\Actions\Garmin\WorkoutFitMapper;
use App\Models\Workout;
use App\Support\Fit\FitEncoder;

class ExportWorkoutFit
{
    public function __construct(
        private WorkoutFitMapper $mapper,
        private FitEncoder $encoder,
    ) {}

    public function execute(Workout $workout): string
    {
        $workout->loadMissing(['sections.blocks.exercises.exerciseable', 'sections.blocks.exercises.exercise']);

        return $this->encoder->encode($this->mapper->map($workout));
    }
}
