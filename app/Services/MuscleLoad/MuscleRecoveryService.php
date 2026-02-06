<?php

declare(strict_types=1);

namespace App\Services\MuscleLoad;

use App\Enums\Workout\MuscleGroup;
use App\Models\User;
use App\Models\WorkoutMuscleLoadSnapshot;
use Carbon\CarbonImmutable;

class MuscleRecoveryService
{
    /**
     * Get recovery status for all muscle groups.
     *
     * @return array<string, array{fatigue_score: float, status: string, ready_for_heavy: bool, muscle_group: MuscleGroup}>
     */
    public function getRecoveryStatus(User $user, ?CarbonImmutable $asOf = null): array
    {
        $asOf ??= CarbonImmutable::now();

        $fourDaysAgo = $asOf->subDays(4);

        $snapshots = WorkoutMuscleLoadSnapshot::query()
            ->whereHas('workout', fn ($query) => $query->where('user_id', $user->id))
            ->where('completed_at', '>=', $fourDaysAgo)
            ->where('completed_at', '<=', $asOf)
            ->get();

        $muscleData = $this->groupSnapshotsByMuscle($snapshots, $asOf);

        return $this->calculateRecoveryStatus($muscleData);
    }

    /**
     * Suggest target muscles based on recovery status.
     *
     * @return array<int, array{muscle_group: MuscleGroup, fatigue_score: float, status: string, ready_for_heavy: bool}>
     */
    public function suggestTargetMuscles(User $user): array
    {
        $recoveryStatus = $this->getRecoveryStatus($user);

        return collect($recoveryStatus)
            ->sortBy('fatigue_score')
            ->values()
            ->all();
    }

    /**
     * Group snapshots by muscle and calculate remaining fatigue.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, WorkoutMuscleLoadSnapshot>  $snapshots
     * @return array<string, array{total_fatigue: float, muscle_group: MuscleGroup}>
     */
    protected function groupSnapshotsByMuscle(
        \Illuminate\Database\Eloquent\Collection $snapshots,
        CarbonImmutable $asOf
    ): array {
        $muscleData = [];

        foreach ($snapshots as $snapshot) {
            $hoursSince = $snapshot->completed_at->diffInHours($asOf, absolute: false);

            if ($hoursSince < 0) {
                continue;
            }

            $recoveryHours = $this->calculateRecoveryHours($snapshot->total_load);
            $remainingFatigue = max(0, 1 - ($hoursSince / $recoveryHours)) * $snapshot->total_load;

            $muscleKey = $snapshot->muscle_group->value;

            if (! isset($muscleData[$muscleKey])) {
                $muscleData[$muscleKey] = [
                    'total_fatigue' => 0.0,
                    'muscle_group' => $snapshot->muscle_group,
                ];
            }

            $muscleData[$muscleKey]['total_fatigue'] += $remainingFatigue;
        }

        return $muscleData;
    }

    /**
     * Calculate required recovery hours based on load.
     */
    protected function calculateRecoveryHours(float $load): int
    {
        if ($load < 30) {
            return 24;
        }

        if ($load < 70) {
            return 48;
        }

        return 72;
    }

    /**
     * Calculate recovery status from muscle fatigue data.
     *
     * @param  array<string, array{total_fatigue: float, muscle_group: MuscleGroup}>  $muscleData
     * @return array<string, array{fatigue_score: float, status: string, ready_for_heavy: bool, muscle_group: MuscleGroup}>
     */
    protected function calculateRecoveryStatus(array $muscleData): array
    {
        $recoveryStatus = [];

        foreach (MuscleGroup::cases() as $muscle) {
            $muscleKey = $muscle->value;
            $fatigueScore = $muscleData[$muscleKey]['total_fatigue'] ?? 0.0;

            $recoveryStatus[$muscleKey] = [
                'fatigue_score' => $fatigueScore,
                'status' => $this->determineRecoveryStatus($fatigueScore),
                'ready_for_heavy' => $fatigueScore < 15,
                'muscle_group' => $muscle,
            ];
        }

        return $recoveryStatus;
    }

    /**
     * Determine recovery status label based on fatigue score.
     */
    protected function determineRecoveryStatus(float $fatigueScore): string
    {
        if ($fatigueScore <= 20) {
            return 'fresh';
        }

        if ($fatigueScore <= 50) {
            return 'recovering';
        }

        return 'fatigued';
    }
}
