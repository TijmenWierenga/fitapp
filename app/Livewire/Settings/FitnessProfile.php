<?php

namespace App\Livewire\Settings;

use App\Enums\FitnessGoal;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

class FitnessProfile extends Component
{
    public ?string $primaryGoal = null;

    public ?string $goalDetails = null;

    public int $availableDaysPerWeek = 3;

    public int $minutesPerSession = 60;

    public bool $preferGarminExercises = false;

    public function mount(): void
    {
        $profile = Auth::user()->fitnessProfile;

        if ($profile) {
            $this->primaryGoal = $profile->primary_goal->value;
            $this->goalDetails = $profile->goal_details;
            $this->availableDaysPerWeek = $profile->available_days_per_week;
            $this->minutesPerSession = $profile->minutes_per_session;
            $this->preferGarminExercises = $profile->prefer_garmin_exercises;
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
        ]);

        Auth::user()->fitnessProfile()->updateOrCreate(
            ['user_id' => Auth::id()],
            [
                'primary_goal' => $validated['primaryGoal'],
                'goal_details' => $validated['goalDetails'],
                'available_days_per_week' => $validated['availableDaysPerWeek'],
                'minutes_per_session' => $validated['minutesPerSession'],
                'prefer_garmin_exercises' => $validated['preferGarminExercises'],
            ]
        );

        $this->dispatch('profile-saved');
    }

    /**
     * @return array<FitnessGoal>
     */
    public function getFitnessGoalsProperty(): array
    {
        return FitnessGoal::cases();
    }
}
