<?php

namespace App\Livewire\Settings;

use App\Enums\BiologicalSex;
use App\Enums\BodyPart;
use App\Enums\Equipment;
use App\Enums\ExperienceLevel;
use App\Enums\FitnessGoal;
use App\Enums\InjuryType;
use App\Models\Injury;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

class FitnessProfile extends Component
{
    public ?string $primaryGoal = null;

    public ?string $goalDetails = null;

    public int $availableDaysPerWeek = 3;

    public int $minutesPerSession = 60;

    public bool $preferGarminExercises = false;

    public ?string $experienceLevel = null;

    public ?string $dateOfBirth = null;

    public ?string $biologicalSex = null;

    public ?string $bodyWeightKg = null;

    public ?int $heightCm = null;

    public bool $hasGymAccess = false;

    /** @var array<string> */
    public array $homeEquipment = [];

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
            $this->preferGarminExercises = $profile->prefer_garmin_exercises;
            $this->experienceLevel = $profile->experience_level?->value;
            $this->dateOfBirth = $profile->date_of_birth?->format('Y-m-d');
            $this->biologicalSex = $profile->biological_sex?->value;
            $this->bodyWeightKg = $profile->body_weight_kg;
            $this->heightCm = $profile->height_cm;
            $this->hasGymAccess = $profile->has_gym_access;
            $this->homeEquipment = $profile->home_equipment ?? [];
        }
    }

    public function saveProfile(): void
    {
        $validated = $this->validate([
            'primaryGoal' => ['required', Rule::enum(FitnessGoal::class)],
            'goalDetails' => ['nullable', 'string', 'max:5000'],
            'availableDaysPerWeek' => ['required', 'integer', 'min:1', 'max:7'],
            'minutesPerSession' => ['required', 'integer', 'min:15', 'max:180'],
            'preferGarminExercises' => ['boolean'],
            'experienceLevel' => ['nullable', Rule::enum(ExperienceLevel::class)],
            'dateOfBirth' => ['nullable', 'date', 'before:today'],
            'biologicalSex' => ['nullable', Rule::enum(BiologicalSex::class)],
            'bodyWeightKg' => ['nullable', 'numeric', 'min:20', 'max:300'],
            'heightCm' => ['nullable', 'integer', 'min:100', 'max:250'],
            'hasGymAccess' => ['boolean'],
            'homeEquipment' => ['array'],
            'homeEquipment.*' => [Rule::enum(Equipment::class)],
        ]);

        Auth::user()->fitnessProfile()->updateOrCreate(
            ['user_id' => Auth::id()],
            [
                'primary_goal' => $validated['primaryGoal'],
                'goal_details' => $validated['goalDetails'],
                'available_days_per_week' => $validated['availableDaysPerWeek'],
                'minutes_per_session' => $validated['minutesPerSession'],
                'prefer_garmin_exercises' => $validated['preferGarminExercises'],
                'experience_level' => $validated['experienceLevel'],
                'date_of_birth' => $validated['dateOfBirth'],
                'biological_sex' => $validated['biologicalSex'],
                'body_weight_kg' => $validated['bodyWeightKg'],
                'height_cm' => $validated['heightCm'],
                'has_gym_access' => $validated['hasGymAccess'],
                'home_equipment' => $validated['homeEquipment'],
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
    #[Computed]
    public function fitnessGoals(): array
    {
        return FitnessGoal::cases();
    }

    /**
     * @return array<ExperienceLevel>
     */
    #[Computed]
    public function experienceLevels(): array
    {
        return ExperienceLevel::cases();
    }

    /**
     * @return array<BiologicalSex>
     */
    #[Computed]
    public function biologicalSexOptions(): array
    {
        return BiologicalSex::cases();
    }

    /**
     * @return array<Equipment>
     */
    #[Computed]
    public function equipmentOptions(): array
    {
        return Equipment::homeEquipmentOptions();
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
     * @return \Illuminate\Database\Eloquent\Collection<int, Injury>
     */
    #[Computed]
    public function injuries(): \Illuminate\Database\Eloquent\Collection
    {
        return Auth::user()->injuries()->orderBy('started_at', 'desc')->get();
    }
}
