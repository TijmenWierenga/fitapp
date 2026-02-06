<?php

declare(strict_types=1);

namespace App\Enums\Workout;

enum BlockType: string
{
    case Group = 'group';
    case Interval = 'interval';
    case ExerciseGroup = 'exercise_group';
    case Rest = 'rest';
    case Note = 'note';

    public function label(): string
    {
        return match ($this) {
            self::ExerciseGroup => 'Exercise Group',
            default => ucfirst($this->value),
        };
    }

    /**
     * Whether this block type requires a polymorphic blockable relation.
     */
    public function hasBlockable(): bool
    {
        return $this !== self::Group;
    }
}
