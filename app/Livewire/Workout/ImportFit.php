<?php

declare(strict_types=1);

namespace App\Livewire\Workout;

use App\Actions\CheckForDuplicateFitImport;
use App\Actions\DetectFitExerciseMismatch;
use App\Actions\ImportFitToWorkout;
use App\DataTransferObjects\Fit\FitActivityPreview;
use App\DataTransferObjects\Fit\ParsedActivity;
use App\Exceptions\FitParseException;
use App\Models\Workout;
use App\Support\Fit\Decode\FitActivityParser;
use App\Support\Workout\DistanceConverter;
use App\Support\Workout\TimeConverter;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class ImportFit extends Component
{
    use WithFileUploads;

    public ?Workout $workout = null;

    public bool $showModal = false;

    public $fitFile;

    public string $step = 'upload';

    public ?FitActivityPreview $preview = null;

    public ?string $duplicateWarning = null;

    public ?string $mismatchWarning = null;

    public ?string $parseError = null;

    public ?int $rpe = null;

    public ?int $feeling = null;

    #[On('import-fit')]
    public function openModal(int $workoutId): void
    {
        $this->workout = auth()->user()->workouts()->findOrFail($workoutId);

        if ($this->workout->isCompleted()) {
            return;
        }

        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->workout = null;
        $this->reset(['fitFile', 'step', 'preview', 'duplicateWarning', 'mismatchWarning', 'parseError', 'rpe', 'feeling']);
    }

    public function updatedFitFile(): void
    {
        $this->validate([
            'fitFile' => ['required', 'file', 'max:10240'],
        ]);

        $this->parseError = null;

        try {
            $fitData = file_get_contents($this->fitFile->getRealPath());
            $parser = app(FitActivityParser::class);
            $parsed = $parser->parse($fitData);

            $this->preview = $this->buildPreview($parsed);

            $duplicate = app(CheckForDuplicateFitImport::class)->execute(auth()->user(), $parsed);
            $this->duplicateWarning = $duplicate !== null
                ? "Possible duplicate: workout '{$duplicate->name}' on {$duplicate->scheduled_at->format('M j, Y')} was already imported from Garmin."
                : null;

            $this->mismatchWarning = app(DetectFitExerciseMismatch::class)->execute($this->workout, $parsed);
            $this->step = 'preview';
        } catch (FitParseException $e) {
            $this->parseError = $e->getMessage();
        }
    }

    public function confirmImport(ImportFitToWorkout $action): void
    {
        $this->authorize('update', $this->workout);

        $this->validate([
            'rpe' => ['nullable', 'integer', 'min:1', 'max:10'],
            'feeling' => ['nullable', 'integer', 'min:1', 'max:5'],
        ]);

        $fitData = file_get_contents($this->fitFile->getRealPath());

        try {
            $action->execute(
                user: auth()->user(),
                workout: $this->workout,
                fitData: $fitData,
                rpe: $this->rpe,
                feeling: $this->feeling,
            );
        } catch (\Throwable $e) {
            report($e);
            $this->parseError = 'Import failed. Please try again or use a different file.';
            $this->step = 'upload';
            $this->reset(['fitFile', 'preview', 'duplicateWarning', 'mismatchWarning', 'rpe', 'feeling']);

            return;
        }

        $this->dispatch('fit-imported');

        $this->redirect(route('workouts.show', $this->workout));
    }

    private function buildPreview(ParsedActivity $parsed): FitActivityPreview
    {
        $activity = \App\Actions\Garmin\SportMapper::toActivity($parsed->session->sport, $parsed->session->subSport);

        return new FitActivityPreview(
            activity: $activity->label(),
            duration: $parsed->session->totalElapsedTime !== null
                ? TimeConverter::format($parsed->session->totalElapsedTime)
                : null,
            distance: $parsed->session->totalDistance !== null
                ? DistanceConverter::format((int) $parsed->session->totalDistance)
                : null,
            calories: $parsed->session->totalCalories,
            avgHeartRate: $parsed->session->avgHeartRate,
            maxHeartRate: $parsed->session->maxHeartRate,
        );
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.workout.import-fit');
    }
}
