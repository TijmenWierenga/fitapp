<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DataTransferObjects\Workout\ExercisePresentation;

interface PresentableExercise
{
    public function present(): ExercisePresentation;
}
