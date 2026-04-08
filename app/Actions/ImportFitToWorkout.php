<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\FitImport;
use App\Models\User;
use App\Models\Workout;
use App\Support\Fit\Decode\FitActivityParser;
use Illuminate\Support\Facades\DB;

class ImportFitToWorkout
{
    public function __construct(
        private FitActivityParser $parser,
        private PopulateFitExerciseSets $populateSets,
        private SetWorkoutSessionSummary $setSessionSummary,
        private CheckForDuplicateFitImport $checkDuplicate,
        private DetectFitExerciseMismatch $detectMismatch,
    ) {}

    /**
     * @return array{warnings: list<string>}
     */
    public function execute(
        User $user,
        Workout $workout,
        string $fitData,
        ?int $rpe = null,
        ?int $feeling = null,
    ): array {
        $parsed = $this->parser->parse($fitData);
        $warnings = [];

        $duplicate = $this->checkDuplicate->execute($user, $parsed);

        if ($duplicate !== null) {
            $warnings[] = "Possible duplicate: workout '{$duplicate->name}' on {$duplicate->scheduled_at->format('M j, Y')} was already imported from Garmin.";
        }

        $mismatchWarning = $this->detectMismatch->execute($workout, $parsed);

        if ($mismatchWarning !== null) {
            $warnings[] = $mismatchWarning;
        }

        DB::transaction(function () use ($user, $workout, $parsed, $fitData, $rpe, $feeling): void {
            $this->populateSets->execute($workout, $parsed);
            $this->setSessionSummary->execute($workout, $parsed);
            $this->markCompleted($workout, $rpe, $feeling);
            $this->storeFitImport($user, $workout, $fitData);
        });

        return ['warnings' => $warnings];
    }

    private function markCompleted(Workout $workout, ?int $rpe, ?int $feeling): void
    {
        $workout->update([
            'completed_at' => $workout->completed_at ?? now(),
            'rpe' => $rpe,
            'feeling' => $feeling,
        ]);
    }

    private function storeFitImport(User $user, Workout $workout, string $fitData): void
    {
        FitImport::create([
            'user_id' => $user->id,
            'workout_id' => $workout->id,
            'raw_data' => base64_encode($fitData),
            'imported_at' => now(),
        ]);
    }
}
