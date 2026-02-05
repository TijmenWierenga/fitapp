<?php

namespace App\Livewire\Settings;

use App\Enums\BodyPart;
use App\Enums\FitnessGoal;
use App\Enums\InjuryType;
use App\Models\Injury;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

class FitnessProfile extends Component
{
    public ?string $primaryGoal = null;

    public ?string $goalDetails = null;

    public int $availableDaysPerWeek = 3;

    public int $minutesPerSession = 60;

    public bool $showInjuryModal = false;

    public ?int $editingInjuryId = null;

    public ?string $injuryType = null;

    public ?string $bodyPart = null;

    public ?string $startedAt = null;

    public ?string $endedAt = null;

    public ?string $injuryNotes = null;

    public function mount(): void
    {
        $profile = Auth::user()->fitnessProfile;

        if ($profile) {
            $this->primaryGoal = $profile->primary_goal->value;
            $this->goalDetails = $profile->goal_details;
            $this->availableDaysPerWeek = $profile->available_days_per_week;
            $this->minutesPerSession = $profile->minutes_per_session;
        }
    }

    public function saveProfile(): void
    {
        $validated = $this->validate([
            'primaryGoal' => ['required', Rule::enum(FitnessGoal::class)],
            'goalDetails' => ['nullable', 'string', 'max:5000'],
            'availableDaysPerWeek' => ['required', 'integer', 'min:1', 'max:7'],
            'minutesPerSession' => ['required', 'integer', 'min:15', 'max:180'],
        ]);

        Auth::user()->fitnessProfile()->updateOrCreate(
            ['user_id' => Auth::id()],
            [
                'primary_goal' => $validated['primaryGoal'],
                'goal_details' => $validated['goalDetails'],
                'available_days_per_week' => $validated['availableDaysPerWeek'],
                'minutes_per_session' => $validated['minutesPerSession'],
            ]
        );

        $this->dispatch('profile-saved');
    }

    public function openInjuryModal(?int $injuryId = null): void
    {
        $this->resetInjuryForm();

        if ($injuryId) {
            $injury = Auth::user()->injuries()->findOrFail($injuryId);
            $this->editingInjuryId = $injury->id;
            $this->injuryType = $injury->injury_type->value;
            $this->bodyPart = $injury->body_part->value;
            $this->startedAt = $injury->started_at->format('Y-m-d');
            $this->endedAt = $injury->ended_at?->format('Y-m-d');
            $this->injuryNotes = $injury->notes;
        }

        $this->showInjuryModal = true;
    }

    public function closeInjuryModal(): void
    {
        $this->showInjuryModal = false;
        $this->resetInjuryForm();
    }

    public function saveInjury(): void
    {
        $validated = $this->validate([
            'injuryType' => ['required', Rule::enum(InjuryType::class)],
            'bodyPart' => ['required', Rule::enum(BodyPart::class)],
            'startedAt' => ['required', 'date'],
            'endedAt' => ['nullable', 'date', 'after_or_equal:startedAt'],
            'injuryNotes' => ['nullable', 'string', 'max:5000'],
        ]);

        $data = [
            'injury_type' => $validated['injuryType'],
            'body_part' => $validated['bodyPart'],
            'started_at' => $validated['startedAt'],
            'ended_at' => $validated['endedAt'],
            'notes' => $validated['injuryNotes'],
        ];

        if ($this->editingInjuryId) {
            Auth::user()->injuries()->where('id', $this->editingInjuryId)->update($data);
        } else {
            Auth::user()->injuries()->create($data);
        }

        $this->closeInjuryModal();
        $this->dispatch('injury-saved');
    }

    public function deleteInjury(int $injuryId): void
    {
        Auth::user()->injuries()->where('id', $injuryId)->delete();
        $this->dispatch('injury-deleted');
    }

    protected function resetInjuryForm(): void
    {
        $this->editingInjuryId = null;
        $this->injuryType = null;
        $this->bodyPart = null;
        $this->startedAt = null;
        $this->endedAt = null;
        $this->injuryNotes = null;
        $this->resetValidation();
    }

    /**
     * @return array<FitnessGoal>
     */
    public function getFitnessGoalsProperty(): array
    {
        return FitnessGoal::cases();
    }

    /**
     * @return array<InjuryType>
     */
    public function getInjuryTypesProperty(): array
    {
        return InjuryType::cases();
    }

    /**
     * @return array<string, array<BodyPart>>
     */
    public function getBodyPartsGroupedProperty(): array
    {
        return BodyPart::groupedByRegion();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Injury>
     */
    public function getInjuriesProperty(): \Illuminate\Database\Eloquent\Collection
    {
        return Auth::user()->injuries()->orderBy('started_at', 'desc')->get();
    }
}
