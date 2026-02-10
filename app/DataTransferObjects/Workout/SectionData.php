<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Workout;

use Illuminate\Support\Collection;

readonly class SectionData
{
    /**
     * @param  Collection<int, BlockData>  $blocks
     */
    public function __construct(
        public string $name,
        public int $order,
        public Collection $blocks,
        public ?string $notes = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $blocks = collect($data['blocks'] ?? [])
            ->map(fn (array $block): BlockData => BlockData::fromArray($block));

        return new self(
            name: $data['name'],
            order: $data['order'],
            blocks: $blocks,
            notes: $data['notes'] ?? null,
        );
    }
}
