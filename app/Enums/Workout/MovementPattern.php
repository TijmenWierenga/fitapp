<?php

declare(strict_types=1);

namespace App\Enums\Workout;

enum MovementPattern: string
{
    case Squat = 'squat';
    case Hinge = 'hinge';
    case Push = 'push';
    case Pull = 'pull';
    case Carry = 'carry';
    case Rotation = 'rotation';
    case Core = 'core';
    case Other = 'other';
}
