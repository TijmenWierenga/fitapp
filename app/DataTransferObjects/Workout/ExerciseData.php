<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Workout;

use App\Enums\Workout\ExerciseType;

readonly class ExerciseData
{
    public function __construct(
        public string $name,
        public int $order,
        public ExerciseType $type,
        public StrengthExerciseData|CardioExerciseData|DurationExerciseData $exerciseable,
        public ?string $notes = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $type = ExerciseType::from($data['type']);

        $exerciseable = match ($type) {
            ExerciseType::Strength => StrengthExerciseData::fromArray($data),
            ExerciseType::Cardio => CardioExerciseData::fromArray($data),
            ExerciseType::Duration => DurationExerciseData::fromArray($data),
        };

        return new self(
            name: $data['name'],
            order: $data['order'],
            type: $type,
            exerciseable: $exerciseable,
            notes: $data['notes'] ?? null,
        );
    }
}
