<?php

namespace App\Livewire\Onboarding;

use App\Enums\BiologicalSex;
use App\Enums\Equipment;
use App\Enums\ExperienceLevel;
use App\Enums\FitnessGoal;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class FitnessProfileWizard extends Component
{
    public int $currentStep = 1;

    // Step 1: About You
    public ?string $experienceLevel = null;

    public ?string $dateOfBirth = null;

    public ?string $biologicalSex = null;

    public ?string $bodyWeightKg = null;

    public ?int $heightCm = null;

    // Step 2: Your Goals
    public ?string $primaryGoal = null;

    public ?string $goalDetails = null;

    public int $availableDaysPerWeek = 3;

    public int $minutesPerSession = 60;

    // Step 3: Your Setup
    public bool $hasGymAccess = false;

    /** @var array<string> */
    public array $homeEquipment = [];

    public bool $preferGarminExercises = false;

    public function mount(): void
    {
        $profile = Auth::user()->fitnessProfile;

        if ($profile) {
            $this->experienceLevel = $profile->experience_level?->value;
            $this->dateOfBirth = $profile->date_of_birth?->format('Y-m-d');
            $this->biologicalSex = $profile->biological_sex?->value;
            $this->bodyWeightKg = $profile->body_weight_kg;
            $this->heightCm = $profile->height_cm;
            $this->primaryGoal = $profile->primary_goal->value;
            $this->goalDetails = $profile->goal_details;
            $this->availableDaysPerWeek = $profile->available_days_per_week;
            $this->minutesPerSession = $profile->minutes_per_session;
            $this->hasGymAccess = $profile->has_gym_access;
            $this->homeEquipment = $profile->home_equipment ?? [];
            $this->preferGarminExercises = $profile->prefer_garmin_exercises;
        }
    }

    public function nextStep(): void
    {
        $this->validateCurrentStep();
        $this->currentStep = min($this->currentStep + 1, 3);
    }

    public function previousStep(): void
    {
        $this->currentStep = max($this->currentStep - 1, 1);
    }

    public function save(): void
    {
        $this->validateCurrentStep();

        Auth::user()->fitnessProfile()->updateOrCreate(
            ['user_id' => Auth::id()],
            [
                'experience_level' => $this->experienceLevel,
                'date_of_birth' => $this->dateOfBirth,
                'biological_sex' => $this->biologicalSex,
                'body_weight_kg' => $this->bodyWeightKg,
                'height_cm' => $this->heightCm,
                'primary_goal' => $this->primaryGoal,
                'goal_details' => $this->goalDetails,
                'available_days_per_week' => $this->availableDaysPerWeek,
                'minutes_per_session' => $this->minutesPerSession,
                'has_gym_access' => $this->hasGymAccess,
                'home_equipment' => $this->homeEquipment,
                'prefer_garmin_exercises' => $this->preferGarminExercises,
            ]
        );

        $this->redirect(route('dashboard'));
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

    private function validateCurrentStep(): void
    {
        match ($this->currentStep) {
            1 => $this->validate([
                'experienceLevel' => ['required', Rule::enum(ExperienceLevel::class)],
                'dateOfBirth' => ['nullable', 'date', 'before:today'],
                'biologicalSex' => ['nullable', Rule::enum(BiologicalSex::class)],
                'bodyWeightKg' => ['nullable', 'numeric', 'min:20', 'max:300'],
                'heightCm' => ['nullable', 'integer', 'min:100', 'max:250'],
            ]),
            2 => $this->validate([
                'primaryGoal' => ['required', Rule::enum(FitnessGoal::class)],
                'goalDetails' => ['nullable', 'string', 'max:5000'],
                'availableDaysPerWeek' => ['required', 'integer', 'min:1', 'max:7'],
                'minutesPerSession' => ['required', 'integer', 'min:15', 'max:180'],
            ]),
            3 => $this->validate([
                'hasGymAccess' => ['boolean'],
                'homeEquipment' => ['array'],
                'homeEquipment.*' => [Rule::enum(Equipment::class)],
                'preferGarminExercises' => ['boolean'],
            ]),
        };
    }
}
