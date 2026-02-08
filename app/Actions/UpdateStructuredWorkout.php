<?php

namespace App\Actions;

use App\DataTransferObjects\Workout\SectionData;
use App\Models\Workout;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UpdateStructuredWorkout
{
    public function __construct(
        private CreateStructuredWorkout $createStructuredWorkout,
    ) {}

    /**
     * @param  Collection<int, SectionData>  $sections
     */
    public function execute(Workout $workout, Collection $sections): void
    {
        DB::transaction(function () use ($workout, $sections): void {
            $this->deleteExistingStructure($workout);
            $this->createStructuredWorkout->buildSections($workout, $sections);
        });
    }

    private function deleteExistingStructure(Workout $workout): void
    {
        $workout->load('sections.blocks.exercises');

        foreach ($workout->sections as $section) {
            foreach ($section->blocks as $block) {
                foreach ($block->exercises as $exercise) {
                    $exercise->exerciseable?->delete();
                }
            }
        }

        // Cascade delete handles block_exercises and blocks via FK constraints
        $workout->sections()->delete();
    }
}
