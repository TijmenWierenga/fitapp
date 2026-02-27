<?php

declare(strict_types=1);

namespace App\Livewire\Injury;

use App\Models\Injury;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Impact extends Component
{
    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Injury>
     */
    #[Computed]
    public function activeInjuries(): \Illuminate\Database\Eloquent\Collection
    {
        return Auth::user()->injuries()->active()->orderBy('started_at', 'desc')->get();
    }

    #[Computed]
    public function activeCount(): int
    {
        return $this->activeInjuries->count();
    }

    #[Computed]
    public function moderateCount(): int
    {
        return $this->activeInjuries
            ->filter(fn (Injury $injury): bool => $injury->severity === \App\Enums\Severity::Moderate)
            ->count();
    }

    #[Computed]
    public function severeCount(): int
    {
        return $this->activeInjuries
            ->filter(fn (Injury $injury): bool => $injury->severity === \App\Enums\Severity::Severe)
            ->count();
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.injury.impact');
    }
}
