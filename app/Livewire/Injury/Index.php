<?php

declare(strict_types=1);

namespace App\Livewire\Injury;

use App\Actions\CreateInjury;
use App\Actions\UpdateInjury;
use App\Enums\BodyPart;
use App\Enums\InjuryType;
use App\Enums\Severity;
use App\Enums\Side;
use App\Models\Injury;
use Carbon\CarbonImmutable;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Index extends Component
{
    public bool $showInjuryModal = false;

    public ?int $editingInjuryId = null;

    public ?string $bodyPart = null;

    public ?string $side = null;

    public ?string $injuryType = null;

    public ?string $severity = null;

    public ?string $startedAt = null;

    public ?string $howItHappened = null;

    public ?string $currentSymptoms = null;

    public ?string $injuryNotes = null;

    public ?int $painLevel = null;

    public ?string $statusUpdate = null;

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Injury>
     */
    #[Computed]
    public function injuries(): \Illuminate\Database\Eloquent\Collection
    {
        return Auth::user()->injuries()->orderBy('started_at', 'desc')->get();
    }

    #[Computed]
    public function activeCount(): int
    {
        return Auth::user()->injuries()->active()->count();
    }

    #[Computed]
    public function recoveringCount(): int
    {
        return Auth::user()->injuries()->resolved()
            ->where('ended_at', '>=', now()->subDays(30))
            ->count();
    }

    #[Computed]
    public function healedCount(): int
    {
        return Auth::user()->injuries()->resolved()
            ->where('ended_at', '<', now()->subDays(30))
            ->count();
    }

    /**
     * @return array<InjuryType>
     */
    #[Computed]
    public function injuryTypes(): array
    {
        return InjuryType::cases();
    }

    /**
     * @return array<string, array<BodyPart>>
     */
    #[Computed]
    public function bodyPartsGrouped(): array
    {
        return BodyPart::groupedByRegion();
    }

    /**
     * @return array<Severity>
     */
    #[Computed]
    public function severities(): array
    {
        return Severity::cases();
    }

    /**
     * @return array<Side>
     */
    #[Computed]
    public function sides(): array
    {
        return Side::cases();
    }

    public function openLogModal(): void
    {
        $this->resetInjuryForm();
        $this->startedAt = now()->format('Y-m-d');
        $this->side = Side::NotApplicable->value;
        $this->showInjuryModal = true;
    }

    public function openEditModal(int $injuryId): void
    {
        $this->resetInjuryForm();

        $injury = Auth::user()->injuries()->findOrFail($injuryId);

        $this->editingInjuryId = $injury->id;
        $this->bodyPart = $injury->body_part->value;
        $this->side = $injury->side?->value;
        $this->injuryType = $injury->injury_type->value;
        $this->severity = $injury->severity?->value;
        $this->startedAt = $injury->started_at->format('Y-m-d');
        $this->howItHappened = $injury->how_it_happened;
        $this->currentSymptoms = $injury->current_symptoms;
        $this->injuryNotes = $injury->notes;

        $this->statusUpdate = $this->resolveStatusFromInjury($injury);

        $this->showInjuryModal = true;
    }

    public function closeModal(): void
    {
        $this->showInjuryModal = false;
        $this->resetInjuryForm();
    }

    public function saveInjury(#[CurrentUser] \App\Models\User $user): void
    {
        if ($this->editingInjuryId) {
            $this->updateInjury($user);
        } else {
            $this->createInjury($user);
        }
    }

    public function markAsHealed(int $injuryId): void
    {
        $injury = Auth::user()->injuries()->findOrFail($injuryId);

        $this->authorize('update', $injury);

        app(UpdateInjury::class)->execute(
            injury: $injury,
            injuryType: $injury->injury_type,
            bodyPart: $injury->body_part,
            startedAt: CarbonImmutable::parse($injury->started_at),
            severity: $injury->severity,
            side: $injury->side,
            endedAt: CarbonImmutable::now(),
            notes: $injury->notes,
            howItHappened: $injury->how_it_happened,
            currentSymptoms: $injury->current_symptoms,
        );

        $this->clearComputedCaches();
    }

    public function deleteInjury(int $injuryId): void
    {
        $injury = Auth::user()->injuries()->findOrFail($injuryId);

        $this->authorize('delete', $injury);

        $injury->delete();

        $this->closeModal();
        $this->clearComputedCaches();
    }

    protected function createInjury(\App\Models\User $user): void
    {
        $this->authorize('create', Injury::class);

        $validated = $this->validate($this->logValidationRules());

        app(CreateInjury::class)->execute(
            user: $user,
            injuryType: InjuryType::from($validated['injuryType']),
            bodyPart: BodyPart::from($validated['bodyPart']),
            startedAt: CarbonImmutable::parse($validated['startedAt']),
            severity: $validated['severity'] ? Severity::from($validated['severity']) : null,
            side: $validated['side'] ? Side::from($validated['side']) : null,
            howItHappened: $validated['howItHappened'],
            currentSymptoms: $validated['currentSymptoms'],
        );

        $this->closeModal();
        $this->clearComputedCaches();
    }

    protected function updateInjury(\App\Models\User $user): void
    {
        $injury = $user->injuries()->findOrFail($this->editingInjuryId);

        $this->authorize('update', $injury);

        $validated = $this->validate($this->editValidationRules());

        $endedAt = $this->resolveEndedAt($injury, $validated['statusUpdate']);

        app(UpdateInjury::class)->execute(
            injury: $injury,
            injuryType: InjuryType::from($validated['injuryType']),
            bodyPart: BodyPart::from($validated['bodyPart']),
            startedAt: CarbonImmutable::parse($validated['startedAt']),
            severity: $validated['severity'] ? Severity::from($validated['severity']) : null,
            side: $validated['side'] ? Side::from($validated['side']) : null,
            endedAt: $endedAt,
            notes: $validated['injuryNotes'],
            howItHappened: $validated['howItHappened'],
            currentSymptoms: $validated['currentSymptoms'],
        );

        $this->closeModal();
        $this->clearComputedCaches();
    }

    /**
     * @return array<string, array<mixed>>
     */
    protected function logValidationRules(): array
    {
        return [
            'bodyPart' => ['required', Rule::enum(BodyPart::class)],
            'side' => ['nullable', Rule::enum(Side::class)],
            'injuryType' => ['required', Rule::enum(InjuryType::class)],
            'severity' => ['nullable', Rule::enum(Severity::class)],
            'startedAt' => ['required', 'date'],
            'howItHappened' => ['nullable', 'string', 'max:5000'],
            'currentSymptoms' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, array<mixed>>
     */
    protected function editValidationRules(): array
    {
        return [
            ...$this->logValidationRules(),
            'injuryNotes' => ['nullable', 'string', 'max:5000'],
            'statusUpdate' => ['required', Rule::in(['active', 'recovering', 'healed'])],
        ];
    }

    protected function resolveStatusFromInjury(Injury $injury): string
    {
        if ($injury->ended_at === null) {
            return 'active';
        }

        if ($injury->ended_at->greaterThanOrEqualTo(now()->subDays(30))) {
            return 'recovering';
        }

        return 'healed';
    }

    protected function resolveEndedAt(Injury $injury, string $statusUpdate): ?CarbonImmutable
    {
        if ($statusUpdate === 'active') {
            return null;
        }

        // If already resolved, keep the existing ended_at date
        if ($injury->ended_at !== null) {
            return CarbonImmutable::parse($injury->ended_at);
        }

        // Transitioning from active to recovering/healed: set ended_at to today
        return CarbonImmutable::now();
    }

    protected function resetInjuryForm(): void
    {
        $this->editingInjuryId = null;
        $this->bodyPart = null;
        $this->side = null;
        $this->injuryType = null;
        $this->severity = null;
        $this->startedAt = null;
        $this->howItHappened = null;
        $this->currentSymptoms = null;
        $this->injuryNotes = null;
        $this->painLevel = null;
        $this->statusUpdate = null;
        $this->resetValidation();
    }

    protected function clearComputedCaches(): void
    {
        unset($this->injuries, $this->activeCount, $this->recoveringCount, $this->healedCount);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.injury.index');
    }
}
