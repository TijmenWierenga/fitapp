<?php

declare(strict_types=1);

namespace App\Livewire\Workout;

use App\Actions\Garmin\ParsedActivityHelper;
use App\Actions\Garmin\SportMapper;
use App\Actions\ImportFitToWorkout;
use App\DataTransferObjects\Fit\ParsedActivity;
use App\Enums\Workout\BlockType;
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

    /** @var array<string, mixed>|null */
    public ?array $preview = null;

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
            $this->duplicateWarning = $this->checkDuplicate($parsed);
            $this->mismatchWarning = $this->detectMismatch($parsed);
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

        $action->execute(
            user: auth()->user(),
            workout: $this->workout,
            fitData: $fitData,
            rpe: $this->rpe,
            feeling: $this->feeling,
        );

        $this->dispatch('fit-imported');

        $this->redirect(route('workouts.show', $this->workout));
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPreview(ParsedActivity $parsed): array
    {
        $activity = SportMapper::toActivity($parsed->session->sport, $parsed->session->subSport);

        return [
            'activity' => $activity->label(),
            'duration' => $parsed->session->totalElapsedTime !== null
                ? TimeConverter::format($parsed->session->totalElapsedTime)
                : null,
            'distance' => $parsed->session->totalDistance !== null
                ? DistanceConverter::format((int) $parsed->session->totalDistance)
                : null,
            'calories' => $parsed->session->totalCalories,
            'avgHeartRate' => $parsed->session->avgHeartRate,
            'maxHeartRate' => $parsed->session->maxHeartRate,
        ];
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

    private function detectMismatch(ParsedActivity $parsed): ?string
    {
        $activeSets = collect($parsed->sets)->filter->isActive();

        if ($activeSets->isEmpty()) {
            return null;
        }

        $groups = ParsedActivityHelper::groupSetsByExercise($activeSets);
        $fitExerciseCount = count($groups);

        $this->workout->loadMissing('sections.blocks.exercises');
        $plannedCount = $this->workout->sections
            ->flatMap(fn ($s) => $s->blocks)
            ->filter(fn ($b) => $b->block_type !== BlockType::DistanceDuration)
            ->flatMap(fn ($b) => $b->exercises)
            ->count();

        if ($fitExerciseCount !== $plannedCount) {
            return "FIT file has {$fitExerciseCount} exercises, planned workout has {$plannedCount}.";
        }

        return null;
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.workout.import-fit');
    }
}
