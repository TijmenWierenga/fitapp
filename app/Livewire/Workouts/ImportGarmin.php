<?php

declare(strict_types=1);

namespace App\Livewire\Workouts;

use App\Actions\ImportGarminActivity;
use App\DataTransferObjects\Fit\ParsedActivity;
use App\Exceptions\FitParseException;
use App\Models\Workout;
use App\Support\Fit\Decode\FindMatchingWorkout;
use App\Support\Fit\Decode\FitActivityParser;
use App\Support\Fit\SportMapper;
use App\Support\Workout\DistanceConverter;
use App\Support\Workout\TimeConverter;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

class ImportGarmin extends Component
{
    use WithFileUploads;

    public $fitFile;

    public string $step = 'upload';

    public ?string $parseError = null;

    #[Url]
    public ?int $workout = null;

    public ?int $selectedWorkoutId = null;

    public ?int $rpe = null;

    public ?int $feeling = null;

    /** @var array<string, mixed>|null */
    public ?array $preview = null;

    /** @var list<array{id: int, name: string, activity: string, scheduled_at: string}>|null */
    public ?array $matchingWorkouts = null;

    /** @var array<string, mixed>|null */
    public ?array $importResultData = null;

    public function mount(): void
    {
        if ($this->workout !== null) {
            $workout = Workout::find($this->workout);

            if (! $workout || $workout->user_id !== auth()->id()) {
                abort(403);
            }

            if ($workout->isCompleted()) {
                session()->flash('error', 'This workout has already been completed.');

                return;
            }

            $this->selectedWorkoutId = $workout->id;
        }
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

            if ($this->selectedWorkoutId === null) {
                $finder = app(FindMatchingWorkout::class);
                $matches = $finder->execute(auth()->user(), $parsed);

                $this->matchingWorkouts = $matches->map(fn (Workout $w) => [
                    'id' => $w->id,
                    'name' => $w->name,
                    'activity' => $w->activity->label(),
                    'scheduled_at' => $w->scheduled_at->format('D, M j \a\t H:i'),
                ])->all();
            }

            $this->step = 'preview';
        } catch (FitParseException $e) {
            $this->parseError = $e->getMessage();
        }
    }

    public function selectWorkout(int $workoutId): void
    {
        $workout = Workout::find($workoutId);

        if (! $workout || $workout->user_id !== auth()->id() || $workout->isCompleted()) {
            return;
        }

        $this->selectedWorkoutId = $workoutId;
    }

    public function createAsNew(): void
    {
        $this->selectedWorkoutId = null;
        $this->step = 'evaluate';
    }

    public function mergeWithSelected(): void
    {
        if ($this->selectedWorkoutId === null) {
            return;
        }

        $this->step = 'evaluate';
    }

    public function confirmImport(): void
    {
        $this->validate([
            'rpe' => ['nullable', 'integer', 'min:1', 'max:10'],
            'feeling' => ['nullable', 'integer', 'min:1', 'max:5'],
        ]);

        $fitData = file_get_contents($this->fitFile->getRealPath());

        $existingWorkout = $this->selectedWorkoutId !== null
            ? Workout::find($this->selectedWorkoutId)
            : null;

        if ($existingWorkout && $existingWorkout->user_id !== auth()->id()) {
            abort(403);
        }

        $action = app(ImportGarminActivity::class);
        $result = $action->execute(
            user: auth()->user(),
            fitData: $fitData,
            existingWorkout: $existingWorkout,
            rpe: $this->rpe,
            feeling: $this->feeling,
        );

        $this->importResultData = [
            'workout_id' => $result->workout->id,
            'workout_name' => $result->workout->name,
            'matched' => $result->matchedExercises,
            'unmatched' => $result->unmatchedExercises,
            'warnings' => $result->warnings,
        ];

        $this->step = 'result';
    }

    public function goToWorkout(): void
    {
        if ($this->importResultData) {
            $this->redirect(route('workouts.show', $this->importResultData['workout_id']));
        }
    }

    public function resetImport(): void
    {
        $this->reset(['fitFile', 'step', 'parseError', 'selectedWorkoutId', 'preview', 'matchingWorkouts', 'importResultData', 'rpe', 'feeling', 'workout']);
        $this->step = 'upload';
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPreview(ParsedActivity $parsed): array
    {
        $activity = SportMapper::toActivity($parsed->session->sport, $parsed->session->subSport);

        return [
            'activity' => $activity->label(),
            'activityIcon' => $activity->icon(),
            'date' => $parsed->session->startTime->format('D, M j, Y \a\t H:i'),
            'duration' => $parsed->session->totalElapsedTime !== null
                ? TimeConverter::format($parsed->session->totalElapsedTime)
                : null,
            'distance' => $parsed->session->totalDistance !== null
                ? DistanceConverter::format((int) $parsed->session->totalDistance)
                : null,
            'calories' => $parsed->session->totalCalories,
            'avgHeartRate' => $parsed->session->avgHeartRate,
            'maxHeartRate' => $parsed->session->maxHeartRate,
            'lapCount' => count($parsed->laps),
            'setCount' => count(array_filter($parsed->sets, fn ($s) => $s->isActive())),
            'exerciseTitleCount' => count($parsed->exerciseTitles),
            'workoutName' => $parsed->session->workoutName,
        ];
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.workouts.import-garmin');
    }
}
