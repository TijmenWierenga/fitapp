<?php

namespace App\Livewire\Injury;

use App\Enums\InjuryReportType;
use App\Models\Injury;
use App\Models\InjuryReport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Reports extends Component
{
    public Injury $injury;

    public bool $showReportModal = false;

    public ?string $reportType = null;

    public ?string $reportContent = null;

    public ?string $reportedAt = null;

    public function mount(Injury $injury): void
    {
        if ($injury->user_id !== Auth::id()) {
            abort(403);
        }

        $this->injury = $injury;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, InjuryReport>
     */
    #[Computed]
    public function reports(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->injury->injuryReports()->with('user')->latest()->get();
    }

    /**
     * @return array<InjuryReportType>
     */
    #[Computed]
    public function reportTypes(): array
    {
        return InjuryReportType::cases();
    }

    public function openReportModal(): void
    {
        $this->resetReportForm();
        $this->reportedAt = now()->format('Y-m-d');
        $this->showReportModal = true;
    }

    public function closeReportModal(): void
    {
        $this->showReportModal = false;
        $this->resetReportForm();
    }

    public function saveReport(): void
    {
        $validated = $this->validate([
            'reportType' => ['required', Rule::enum(InjuryReportType::class)],
            'reportContent' => ['required', 'string', 'max:10000'],
            'reportedAt' => ['required', 'date'],
        ]);

        $this->injury->injuryReports()->create([
            'user_id' => Auth::id(),
            'type' => $validated['reportType'],
            'content' => $validated['reportContent'],
            'reported_at' => $validated['reportedAt'],
        ]);

        $this->closeReportModal();
        unset($this->reports);
    }

    public function deleteReport(int $reportId): void
    {
        $report = $this->injury->injuryReports()->where('id', $reportId)->first();

        if (! $report) {
            return;
        }

        if (Auth::id() !== $report->user_id && Auth::id() !== $this->injury->user_id) {
            return;
        }

        $report->delete();
        unset($this->reports);
    }

    protected function resetReportForm(): void
    {
        $this->reportType = null;
        $this->reportContent = null;
        $this->reportedAt = null;
        $this->resetValidation();
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.injury.reports');
    }
}
