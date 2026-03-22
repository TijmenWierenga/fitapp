<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Workload;

use App\Domain\Workload\Enums\AcwrZone;
use App\Domain\Workload\Results\DailyEwmaPoint;
use App\Domain\Workload\Results\EwmaLoadResult;
use App\Domain\Workload\Results\MuscleGroupVolumeResult;
use App\Domain\Workload\Results\SessionLoadResult;
use App\Domain\Workload\Results\StrengthProgressionResult;
use App\Domain\Workload\Results\WeekSummary;
use Illuminate\Support\Collection;

readonly class WorkloadSummary
{
    /**
     * @param  Collection<int, MuscleGroupVolumeResult>  $muscleGroupVolume
     * @param  array<StrengthProgressionResult>  $strengthProgression
     * @param  Collection<int, array<string, mixed>>  $activeInjuries
     */
    public function __construct(
        public ?SessionLoadResult $sessionLoad,
        public ?EwmaLoadResult $ewmaLoad,
        public Collection $muscleGroupVolume,
        public array $strengthProgression,
        public Collection $activeInjuries,
        public int $unlinkedExerciseCount,
        public int $dataSpanDays,
    ) {}

    /**
     * @return Collection<int, string>
     */
    public function warnings(): Collection
    {
        $warnings = collect();

        if ($this->ewmaLoad?->acwrZone === AcwrZone::Caution) {
            $warnings->push("ACWR ({$this->ewmaLoad->acwr}) is in the caution zone (1.3–1.5) — consider reducing or holding volume.");
        }

        if ($this->ewmaLoad?->acwrZone === AcwrZone::Danger) {
            $warnings->push("ACWR ({$this->ewmaLoad->acwr}) is in the danger zone (> 1.5) — significantly reduce volume or rest.");
        }

        if ($this->sessionLoad?->weekOverWeekWarning) {
            $change = $this->sessionLoad->weekOverWeekChangePct;
            $direction = $change > 0 ? 'increase' : 'decrease';
            $warnings->push("Week-over-week load {$direction} of ".abs($change).'% exceeds 15% threshold.');
        }

        if ($this->sessionLoad?->monotonyWarning) {
            $warnings->push("Training monotony ({$this->sessionLoad->monotony}) exceeds 2.0 — consider varying session intensity.");
        }

        if ($this->unlinkedExerciseCount > 0) {
            $warnings->push("{$this->unlinkedExerciseCount} exercise(s) in recent workouts are not linked to the exercise library and are excluded from workload tracking.");
        }

        return $warnings;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'session_load' => $this->sessionLoad ? [
                'current_weekly_total' => $this->sessionLoad->currentWeeklyTotal,
                'current_session_count' => $this->sessionLoad->currentSessionCount,
                'monotony' => $this->sessionLoad->monotony,
                'strain' => $this->sessionLoad->strain,
                'week_over_week_change_pct' => $this->sessionLoad->weekOverWeekChangePct,
                'previous_weeks' => array_map(fn (WeekSummary $w): array => [
                    'week_offset' => $w->weekOffset,
                    'total_load' => $w->totalLoad,
                    'session_count' => $w->sessionCount,
                ], $this->sessionLoad->previousWeeks),
            ] : null,
            'ewma_load' => $this->ewmaLoad ? [
                'acute_load' => $this->ewmaLoad->currentAcuteLoad,
                'chronic_load' => $this->ewmaLoad->currentChronicLoad,
                'acwr' => $this->ewmaLoad->acwr,
                'tsb' => $this->ewmaLoad->tsb,
                'acwr_zone' => $this->ewmaLoad->acwrZone->value,
                'total_sessions' => $this->ewmaLoad->totalSessions,
                'daily_points' => array_map(fn (DailyEwmaPoint $p): array => [
                    'date' => $p->date,
                    'acute_load' => $p->acuteLoad,
                    'chronic_load' => $p->chronicLoad,
                    'acwr' => $p->acwr,
                    'tsb' => $p->tsb,
                ], $this->ewmaLoad->dailyPoints),
            ] : null,
            'muscle_group_volume' => $this->muscleGroupVolume->map(fn (MuscleGroupVolumeResult $v): array => [
                'muscle_group' => $v->name,
                'label' => $v->label,
                'body_part' => $v->bodyPart,
                'current_week_sets' => $v->currentWeekSets,
                'four_week_average_sets' => $v->fourWeekAverageSets,
                'trend' => $v->trend->value,
            ])->values()->toArray(),
            'strength_progression' => array_map(fn (StrengthProgressionResult $s): array => [
                'exercise_id' => $s->exerciseId,
                'exercise_name' => $s->exerciseName,
                'current_e1rm' => $s->currentE1RM,
                'previous_e1rm' => $s->previousE1RM,
                'change_pct' => $s->changePct,
                'current_max_weight' => $s->currentMaxWeight,
                'previous_max_weight' => $s->previousMaxWeight,
                'current_volume' => $s->currentVolume,
            ], $this->strengthProgression),
            'active_injuries' => $this->activeInjuries->toArray(),
            'warnings' => $this->warnings()->values()->toArray(),
            'unlinked_exercise_count' => $this->unlinkedExerciseCount,
            'data_span_days' => $this->dataSpanDays,
        ];
    }
}
