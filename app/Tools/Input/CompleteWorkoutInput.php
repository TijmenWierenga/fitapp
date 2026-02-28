<?php

declare(strict_types=1);

namespace App\Tools\Input;

use App\DataTransferObjects\Workout\PainScore;

readonly class CompleteWorkoutInput
{
    /**
     * @param  list<PainScore>  $painScores
     */
    public function __construct(
        public int $workoutId,
        public int $rpe,
        public int $feeling,
        public array $painScores = [],
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $painScores = array_map(
            fn (array $item): PainScore => PainScore::fromArray($item),
            $data['pain_scores'] ?? [],
        );

        return new self(
            workoutId: $data['workout_id'],
            rpe: $data['rpe'],
            feeling: $data['feeling'],
            painScores: $painScores,
        );
    }
}
