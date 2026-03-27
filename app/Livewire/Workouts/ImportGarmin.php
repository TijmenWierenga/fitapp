<?php

declare(strict_types=1);

namespace App\Livewire\Workouts;

use App\Actions\Garmin\FindMatchingWorkout;
use App\Actions\Garmin\ParsedActivityHelper;
use App\Actions\Garmin\SportMapper;
use App\Actions\ImportGarminActivity;
use App\DataTransferObjects\Fit\ParsedActivity;
use App\Exceptions\FitParseException;
use App\Models\Exercise;
use App\Models\Workout;
use App\Support\Fit\Decode\FitActivityParser;
use App\Support\Workout\DistanceConverter;
use App\Support\Workout\TimeConverter;
use App\Support\Workout\WorkoutDisplayFormatter;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
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

    public ?string $duplicateWarning = null;

    /** @var array<string, mixed>|null */
    public ?array $importResultData = null;

    /** @var list<array{index: int, sets: int, reps: string, weight: string}> */
    public array $exerciseGroups = [];

    /** @var array<int, int|null> */
    public array $exerciseMappings = [];

    public string $exerciseSearch = '';

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
            $this->duplicateWarning = $this->checkDuplicate($parsed);
            $this->exerciseGroups = $this->detectExerciseGroups($parsed);

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
        $this->proceedToMapOrEvaluate();
    }

    public function mergeWithSelected(): void
    {
        if ($this->selectedWorkoutId === null) {
            return;
        }

        $this->prepopulateMappingsFromWorkout($this->selectedWorkoutId);
        $this->proceedToMapOrEvaluate();
    }

    public function proceedToEvaluate(): void
    {
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
            exerciseMappings: array_filter($this->exerciseMappings, fn ($v) => $v !== null),
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
        $this->reset(['fitFile', 'step', 'parseError', 'selectedWorkoutId', 'preview', 'matchingWorkouts', 'importResultData', 'rpe', 'feeling', 'workout', 'duplicateWarning', 'exerciseGroups', 'exerciseMappings', 'exerciseSearch']);
        $this->step = 'upload';
    }

    /**
     * @return Collection<int, Exercise>
     */
    #[Computed]
    public function searchResults(): Collection
    {
        if (strlen($this->exerciseSearch) < 2) {
            return collect();
        }

        return Exercise::search($this->exerciseSearch)
            ->query(fn ($builder) => $builder->with('muscleGroups'))
            ->take(10)
            ->get();
    }

    private function proceedToMapOrEvaluate(): void
    {
        if (count($this->exerciseGroups) > 0) {
            $this->step = 'map';
        } else {
            $this->step = 'evaluate';
        }
    }

    private function prepopulateMappingsFromWorkout(int $workoutId): void
    {
        $workout = Workout::with('sections.blocks.exercises')->find($workoutId);

        if (! $workout) {
            return;
        }

        $plannedExercises = $workout->sections
            ->flatMap(fn ($s) => $s->blocks)
            ->flatMap(fn ($b) => $b->exercises)
            ->values();

        foreach ($this->exerciseGroups as $index => $group) {
            $planned = $plannedExercises[$index] ?? null;

            if ($planned?->exercise_id) {
                $this->exerciseMappings[$group['index']] = $planned->exercise_id;
            }
        }
    }

    /**
     * @return list<array{index: int, sets: int, reps: string, weight: string}>
     */
    private function detectExerciseGroups(ParsedActivity $parsed): array
    {
        $activeSets = collect($parsed->sets)->filter->isActive();

        if ($activeSets->isEmpty()) {
            return [];
        }

        $groups = ParsedActivityHelper::groupSetsByExercise($activeSets);
        $blocks = ParsedActivityHelper::detectBlocks($groups);

        $exerciseIndex = 0;
        $result = [];

        foreach ($blocks as $block) {
            foreach ($block['exercises'] as $exerciseInfo) {
                $sets = $exerciseInfo['sets'];
                $reps = collect($sets)->pluck('repetitions')->filter();
                $weights = collect($sets)->pluck('weight')->filter(fn ($w) => $w !== null && $w > 0);

                $repsDisplay = $reps->isNotEmpty()
                    ? ($reps->min() === $reps->max() ? "{$reps->first()} reps" : "{$reps->min()}-{$reps->max()} reps")
                    : 'timed';

                $weightDisplay = $weights->isNotEmpty()
                    ? WorkoutDisplayFormatter::weight($weights->max())
                    : 'BW';

                $result[] = [
                    'index' => $exerciseIndex,
                    'sets' => count($sets),
                    'reps' => $repsDisplay,
                    'weight' => $weightDisplay,
                ];

                $exerciseIndex++;
            }
        }

        return $result;
    }

    private function checkDuplicate(ParsedActivity $parsed): ?string
    {
        $activity = SportMapper::toActivity($parsed->session->sport, $parsed->session->subSport);

        $duplicate = Workout::query()
            ->where('user_id', auth()->id())
            ->where('activity', $activity)
            ->where('source', 'garmin_fit')
            ->whereDate('scheduled_at', $parsed->session->startTime->toDateString())
            ->first();

        if ($duplicate === null) {
            return null;
        }

        return "Possible duplicate: workout '{$duplicate->name}' on {$duplicate->scheduled_at->format('M j, Y')} was already imported from Garmin.";
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
