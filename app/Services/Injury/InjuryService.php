<?php

declare(strict_types=1);

namespace App\Services\Injury;

use App\Data\InjuryData;
use App\Models\Injury;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;

class InjuryService
{
    public function add(User $user, InjuryData $data): Injury
    {
        return $user->injuries()->create([
            'injury_type' => $data->injuryType,
            'body_part' => $data->bodyPart,
            'started_at' => $data->startedAt,
            'ended_at' => $data->endedAt,
            'notes' => $data->notes,
        ]);
    }

    public function remove(User $user, Injury $injury): bool
    {
        Gate::forUser($user)->authorize('delete', $injury);

        return (bool) $injury->delete();
    }

    public function find(User $user, int $injuryId): ?Injury
    {
        $injury = $user->injuries()->find($injuryId);

        if ($injury) {
            Gate::forUser($user)->authorize('view', $injury);
        }

        return $injury;
    }

    /**
     * @return Collection<int, Injury>
     */
    public function listActive(User $user): Collection
    {
        return $user->activeInjuries()->get();
    }

    /**
     * @return Collection<int, Injury>
     */
    public function listAll(User $user): Collection
    {
        return $user->injuries()->get();
    }
}
