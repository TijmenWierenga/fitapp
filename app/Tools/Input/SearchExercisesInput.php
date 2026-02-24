<?php

declare(strict_types=1);

namespace App\Tools\Input;

readonly class SearchExercisesInput
{
    /**
     * @param  array<string>|null  $queries
     * @param  array<string>  $providedFields
     */
    public function __construct(
        public ?string $query,
        public ?array $queries,
        public ?string $muscleGroup,
        public ?string $category,
        public ?string $equipment,
        public ?string $level,
        public ?bool $garminCompatible,
        public int $limit,
        private array $providedFields,
    ) {}

    public function has(string $field): bool
    {
        return in_array($field, $this->providedFields, true);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            query: $data['query'] ?? null,
            queries: $data['queries'] ?? null,
            muscleGroup: $data['muscle_group'] ?? null,
            category: $data['category'] ?? null,
            equipment: $data['equipment'] ?? null,
            level: $data['level'] ?? null,
            garminCompatible: $data['garmin_compatible'] ?? null,
            limit: min($data['limit'] ?? 20, 50),
            providedFields: array_keys($data),
        );
    }
}
