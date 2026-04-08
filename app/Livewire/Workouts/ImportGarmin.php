<?php

declare(strict_types=1);

namespace App\Livewire\Workouts;

use App\Actions\CheckForDuplicateFitImport;
use App\Enums\FitImportStatus;
use App\Exceptions\FitParseException;
use App\Models\FitImport;
use App\Support\Fit\Decode\FitActivityParser;
use Livewire\Component;
use Livewire\WithFileUploads;

class ImportGarmin extends Component
{
    use WithFileUploads;

    public $fitFile;

    public string $step = 'upload';

    public ?string $parseError = null;

    public ?string $duplicateWarning = null;

    public ?int $pendingImportId = null;

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

            $fitImport = FitImport::create([
                'user_id' => auth()->id(),
                'status' => FitImportStatus::Pending,
                'raw_data' => base64_encode($fitData),
            ]);

            $duplicate = app(CheckForDuplicateFitImport::class)->execute(auth()->user(), $parsed);

            if ($duplicate !== null) {
                $this->duplicateWarning = "Possible duplicate: workout '{$duplicate->name}' on {$duplicate->scheduled_at->format('M j, Y')} was already imported from Garmin.";
                $this->pendingImportId = $fitImport->id;
                $this->step = 'duplicate_warning';

                return;
            }

            $this->redirect(route('workouts.create', ['import' => $fitImport->id]));
        } catch (FitParseException $e) {
            $this->parseError = $e->getMessage();
        }
    }

    public function confirmImportAnyway(): void
    {
        if ($this->pendingImportId === null) {
            $this->resetImport();

            return;
        }

        $this->redirect(route('workouts.create', ['import' => $this->pendingImportId]));
    }

    public function resetImport(): void
    {
        if ($this->pendingImportId !== null) {
            FitImport::where('id', $this->pendingImportId)
                ->where('user_id', auth()->id())
                ->where('status', FitImportStatus::Pending)
                ->delete();
        }

        $this->reset(['fitFile', 'step', 'parseError', 'duplicateWarning', 'pendingImportId']);
        $this->step = 'upload';
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.workouts.import-garmin');
    }
}
