<?php

declare(strict_types=1);

namespace App\Livewire\Workouts;

use App\Actions\Garmin\SportMapper;
use App\DataTransferObjects\Fit\FitImportContext;
use App\DataTransferObjects\Fit\ParsedActivity;
use App\Exceptions\FitParseException;
use App\Models\Workout;
use App\Support\Fit\Decode\FitActivityParser;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class ImportGarmin extends Component
{
    use WithFileUploads;

    public $fitFile;

    public string $step = 'upload';

    public ?string $parseError = null;

    public ?string $duplicateWarning = null;

    public ?string $pendingImportKey = null;

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

            $uuid = (string) Str::uuid();
            $this->storeImportContext($uuid, $parsed, $fitData);

            $this->duplicateWarning = $this->checkDuplicate($parsed);

            if ($this->duplicateWarning !== null) {
                $this->pendingImportKey = $uuid;
                $this->step = 'duplicate_warning';

                return;
            }

            $this->redirect(route('workouts.create', ['import' => $uuid]));
        } catch (FitParseException $e) {
            $this->parseError = $e->getMessage();
        }
    }

    public function confirmImportAnyway(): void
    {
        if ($this->pendingImportKey === null) {
            $this->resetImport();

            return;
        }

        $this->redirect(route('workouts.create', ['import' => $this->pendingImportKey]));
    }

    public function resetImport(): void
    {
        if ($this->pendingImportKey !== null) {
            Cache::forget("fit_import:{$this->pendingImportKey}");
        }

        $this->reset(['fitFile', 'step', 'parseError', 'duplicateWarning', 'pendingImportKey']);
        $this->step = 'upload';
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.workouts.import-garmin');
    }

    private function storeImportContext(string $uuid, ParsedActivity $parsed, string $fitData): void
    {
        Cache::put(
            "fit_import:{$uuid}",
            new FitImportContext($parsed, $fitData),
            now()->addMinutes(30),
        );
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
}
