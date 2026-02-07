<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Workout;

use App\Enums\Workout\BlockType;
use Illuminate\Support\Collection;

readonly class BlockData
{
    /**
     * @param  Collection<int, ExerciseData>  $exercises
     */
    public function __construct(
        public BlockType $blockType,
        public int $order,
        public Collection $exercises,
        public ?int $rounds = null,
        public ?int $restBetweenExercises = null,
        public ?int $restBetweenRounds = null,
        public ?int $timeCap = null,
        public ?int $workInterval = null,
        public ?int $restInterval = null,
        public ?string $notes = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $exercises = collect($data['exercises'] ?? [])
            ->map(fn (array $exercise): ExerciseData => ExerciseData::fromArray($exercise));

        return new self(
            blockType: BlockType::from($data['block_type']),
            order: $data['order'],
            exercises: $exercises,
            rounds: $data['rounds'] ?? null,
            restBetweenExercises: $data['rest_between_exercises'] ?? null,
            restBetweenRounds: $data['rest_between_rounds'] ?? null,
            timeCap: $data['time_cap'] ?? null,
            workInterval: $data['work_interval'] ?? null,
            restInterval: $data['rest_interval'] ?? null,
            notes: $data['notes'] ?? null,
        );
    }
}
