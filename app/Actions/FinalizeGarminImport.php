<?php

declare(strict_types=1);

namespace App\Actions;

use App\DataTransferObjects\Workout\SectionData;
use App\Enums\FitImportStatus;
use App\Enums\Workout\Activity;
use App\Models\FitImport;
use App\Models\User;
use App\Models\Workout;
use App\Support\Fit\Decode\FitActivityParser;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FinalizeGarminImport
{
    public function __construct(
        private CreateStructuredWorkout $createWorkout,
        private PopulateFitExerciseSets $populateSets,
        private SetWorkoutSessionSummary $setSessionSummary,
        private FitActivityParser $parser,
    ) {}

    /**
     * @param  Collection<int, SectionData>  $sections
     */
    public function execute(
        User $user,
        FitImport $fitImport,
        Collection $sections,
        string $name,
        Activity $activity,
        CarbonImmutable $scheduledAt,
        ?string $notes,
        ?int $rpe,
        ?int $feeling,
    ): Workout {
        $parsed = $this->parser->parse(base64_decode($fitImport->raw_data));

        return DB::transaction(function () use ($user, $fitImport, $parsed, $sections, $name, $activity, $scheduledAt, $notes, $rpe, $feeling): Workout {
            $workout = $this->createWorkout->execute(
                user: $user,
                name: $name,
                activity: $activity,
                scheduledAt: $scheduledAt,
                notes: $notes,
                sections: $sections,
            );

            $this->populateSets->execute($workout, $parsed);
            $this->setSessionSummary->execute($workout, $parsed);
            $this->markCompleted($workout, $scheduledAt, $rpe, $feeling);

            $fitImport->update([
                'workout_id' => $workout->id,
                'status' => FitImportStatus::Completed,
                'imported_at' => now(),
            ]);

            return $workout->fresh(['sections.blocks.exercises.exerciseable']);
        });
    }

    private function markCompleted(Workout $workout, CarbonImmutable $scheduledAt, ?int $rpe, ?int $feeling): void
    {
        $workout->update([
            'completed_at' => $scheduledAt,
            'rpe' => $rpe,
            'feeling' => $feeling,
        ]);
    }
}
