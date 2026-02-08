<?php

namespace App\Livewire\Workout;

use App\Actions\CreateStructuredWorkout;
use App\Actions\UpdateStructuredWorkout;
use App\DataTransferObjects\Workout\SectionData;
use App\Enums\Workout\Activity;
use App\Enums\Workout\BlockType;
use App\Models\Workout;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Builder extends Component
{
    public ?Workout $workout = null;

    public string $name = '';

    public ?string $notes = null;

    public Activity $activity = Activity::Run;

    public string $scheduled_date = '';

    public string $scheduled_time = '';

    /** @var array<int, array<string, mixed>> */
    public array $sections = [];

    public function mount(?Workout $workout = null): void
    {
        if ($workout && $workout->exists) {
            if (! $workout->canBeEdited()) {
                abort(403, 'Completed workouts cannot be edited.');
            }

            $this->workout = $workout;
            $this->name = $workout->name;
            $this->notes = $workout->notes;
            $this->activity = $workout->activity;
            $this->scheduled_date = $workout->scheduled_at->format('Y-m-d');
            $this->scheduled_time = $workout->scheduled_at->format('H:i');
            $this->sections = $this->hydrateFromWorkout($workout);
        } else {
            $this->scheduled_date = now()->format('Y-m-d');
            $this->scheduled_time = now()->format('H:i');
        }
    }

    public function selectActivity(Activity $activity): void
    {
        $this->activity = $activity;
    }

    public function addSection(): void
    {
        $this->sections[] = [
            '_key' => uniqid('sec_'),
            'name' => '',
            'notes' => null,
            'blocks' => [],
        ];
    }

    public function removeSection(int $index): void
    {
        array_splice($this->sections, $index, 1);
    }

    public function addBlock(int $sectionIndex): void
    {
        $this->sections[$sectionIndex]['blocks'][] = [
            '_key' => uniqid('blk_'),
            'block_type' => BlockType::StraightSets->value,
            'rounds' => null,
            'rest_between_exercises' => null,
            'rest_between_rounds' => null,
            'time_cap' => null,
            'work_interval' => null,
            'rest_interval' => null,
            'notes' => null,
            'exercises' => [],
        ];
    }

    public function removeBlock(int $sectionIndex, int $blockIndex): void
    {
        array_splice($this->sections[$sectionIndex]['blocks'], $blockIndex, 1);
    }

    public function addExercise(int $sectionIndex, int $blockIndex): void
    {
        $this->sections[$sectionIndex]['blocks'][$blockIndex]['exercises'][] = [
            '_key' => uniqid('ex_'),
            'name' => '',
            'type' => 'strength',
            'notes' => null,
            'target_sets' => null,
            'target_reps_min' => null,
            'target_reps_max' => null,
            'target_weight' => null,
            'target_rpe' => null,
            'target_tempo' => null,
            'rest_after' => null,
            'target_duration' => null,
            'target_distance' => null,
            'target_pace_min' => null,
            'target_pace_max' => null,
            'target_heart_rate_zone' => null,
            'target_heart_rate_min' => null,
            'target_heart_rate_max' => null,
            'target_power' => null,
        ];
    }

    public function removeExercise(int $sectionIndex, int $blockIndex, int $exerciseIndex): void
    {
        array_splice($this->sections[$sectionIndex]['blocks'][$blockIndex]['exercises'], $exerciseIndex, 1);
    }

    public function sortSections(string $key, int $position): void
    {
        $currentIndex = collect($this->sections)->search(fn (array $s): bool => $s['_key'] === $key);

        if ($currentIndex === false || $currentIndex === $position) {
            return;
        }

        $item = array_splice($this->sections, $currentIndex, 1)[0];
        array_splice($this->sections, $position, 0, [$item]);
    }

    public function sortBlocks(string $key, int $position): void
    {
        foreach ($this->sections as $si => $section) {
            $currentIndex = collect($section['blocks'])->search(fn (array $b): bool => $b['_key'] === $key);

            if ($currentIndex === false) {
                continue;
            }

            if ($currentIndex === $position) {
                return;
            }

            $item = array_splice($this->sections[$si]['blocks'], $currentIndex, 1)[0];
            array_splice($this->sections[$si]['blocks'], $position, 0, [$item]);

            return;
        }
    }

    public function sortExercises(string $key, int $position): void
    {
        foreach ($this->sections as $si => $section) {
            foreach ($section['blocks'] as $bi => $block) {
                $currentIndex = collect($block['exercises'])->search(fn (array $e): bool => $e['_key'] === $key);

                if ($currentIndex === false) {
                    continue;
                }

                if ($currentIndex === $position) {
                    return;
                }

                $item = array_splice($this->sections[$si]['blocks'][$bi]['exercises'], $currentIndex, 1)[0];
                array_splice($this->sections[$si]['blocks'][$bi]['exercises'], $position, 0, [$item]);

                return;
            }
        }
    }

    public function saveWorkout(): void
    {
        $this->validate($this->validationRules());

        if ($this->workout && $this->workout->exists && ! $this->workout->fresh()->canBeEdited()) {
            abort(403, 'Completed workouts cannot be edited.');
        }

        $scheduledAt = CarbonImmutable::parse("{$this->scheduled_date} {$this->scheduled_time}");
        $sectionDtos = $this->buildSectionDtos();

        if ($this->workout && $this->workout->exists) {
            $this->workout->update([
                'name' => $this->name,
                'notes' => $this->notes,
                'activity' => $this->activity,
                'scheduled_at' => $scheduledAt,
            ]);

            app(UpdateStructuredWorkout::class)->execute($this->workout, $sectionDtos);
        } else {
            $this->workout = app(CreateStructuredWorkout::class)->execute(
                user: auth()->user(),
                name: $this->name,
                activity: $this->activity,
                scheduledAt: $scheduledAt,
                notes: $this->notes,
                sections: $sectionDtos,
            );
        }

        $this->redirect(route('workouts.show', $this->workout));
    }

    public function render(): View
    {
        return view('livewire.workout.builder');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function hydrateFromWorkout(Workout $workout): array
    {
        $workout->load('sections.blocks.exercises.exerciseable');

        return $workout->sections->map(fn ($section): array => [
            '_key' => uniqid('sec_'),
            'name' => $section->name,
            'notes' => $section->notes,
            'blocks' => $section->blocks->map(fn ($block): array => [
                '_key' => uniqid('blk_'),
                'block_type' => $block->block_type->value,
                'rounds' => $block->rounds,
                'rest_between_exercises' => $block->rest_between_exercises,
                'rest_between_rounds' => $block->rest_between_rounds,
                'time_cap' => $block->time_cap,
                'work_interval' => $block->work_interval,
                'rest_interval' => $block->rest_interval,
                'notes' => $block->notes,
                'exercises' => $block->exercises->map(fn ($exercise): array => [
                    '_key' => uniqid('ex_'),
                    'name' => $exercise->name,
                    'type' => $this->morphClassToType($exercise->exerciseable->getMorphClass()),
                    'notes' => $exercise->notes,
                    ...$this->hydrateExerciseable($exercise->exerciseable),
                ])->all(),
            ])->all(),
        ])->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function hydrateExerciseable(object $exerciseable): array
    {
        return match ($exerciseable->getMorphClass()) {
            'strength_exercise' => [
                'target_sets' => $exerciseable->target_sets,
                'target_reps_min' => $exerciseable->target_reps_min,
                'target_reps_max' => $exerciseable->target_reps_max,
                'target_weight' => $exerciseable->target_weight,
                'target_rpe' => $exerciseable->target_rpe,
                'target_tempo' => $exerciseable->target_tempo,
                'rest_after' => $exerciseable->rest_after,
                'target_duration' => null,
                'target_distance' => null,
                'target_pace_min' => null,
                'target_pace_max' => null,
                'target_heart_rate_zone' => null,
                'target_heart_rate_min' => null,
                'target_heart_rate_max' => null,
                'target_power' => null,
            ],
            'cardio_exercise' => [
                'target_sets' => null,
                'target_reps_min' => null,
                'target_reps_max' => null,
                'target_weight' => null,
                'target_tempo' => null,
                'rest_after' => null,
                'target_duration' => $exerciseable->target_duration,
                'target_distance' => $exerciseable->target_distance,
                'target_pace_min' => $exerciseable->target_pace_min,
                'target_pace_max' => $exerciseable->target_pace_max,
                'target_heart_rate_zone' => $exerciseable->target_heart_rate_zone,
                'target_heart_rate_min' => $exerciseable->target_heart_rate_min,
                'target_heart_rate_max' => $exerciseable->target_heart_rate_max,
                'target_power' => $exerciseable->target_power,
                'target_rpe' => null,
            ],
            'duration_exercise' => [
                'target_sets' => null,
                'target_reps_min' => null,
                'target_reps_max' => null,
                'target_weight' => null,
                'target_tempo' => null,
                'rest_after' => null,
                'target_duration' => $exerciseable->target_duration,
                'target_rpe' => $exerciseable->target_rpe,
                'target_distance' => null,
                'target_pace_min' => null,
                'target_pace_max' => null,
                'target_heart_rate_zone' => null,
                'target_heart_rate_min' => null,
                'target_heart_rate_max' => null,
                'target_power' => null,
            ],
        };
    }

    private function toNullableInt(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }

    private function toNullableFloat(mixed $value): ?float
    {
        return $value === null || $value === '' ? null : (float) $value;
    }

    private function morphClassToType(string $morphClass): string
    {
        return match ($morphClass) {
            'strength_exercise' => 'strength',
            'cardio_exercise' => 'cardio',
            'duration_exercise' => 'duration',
        };
    }

    /**
     * @return Collection<int, SectionData>
     */
    private function buildSectionDtos(): Collection
    {
        return collect($this->sections)
            ->values()
            ->map(fn (array $section, int $index): array => [
                'name' => $section['name'],
                'order' => $index,
                'notes' => $section['notes'],
                'blocks' => collect($section['blocks'])
                    ->values()
                    ->map(fn (array $block, int $bi): array => [
                        'block_type' => $block['block_type'],
                        'order' => $bi,
                        'rounds' => $this->toNullableInt($block['rounds']),
                        'rest_between_exercises' => $this->toNullableInt($block['rest_between_exercises']),
                        'rest_between_rounds' => $this->toNullableInt($block['rest_between_rounds']),
                        'time_cap' => $this->toNullableInt($block['time_cap']),
                        'work_interval' => $this->toNullableInt($block['work_interval']),
                        'rest_interval' => $this->toNullableInt($block['rest_interval']),
                        'notes' => $block['notes'],
                        'exercises' => collect($block['exercises'])
                            ->values()
                            ->map(fn (array $exercise, int $ei): array => [
                                'name' => $exercise['name'],
                                'order' => $ei,
                                'type' => $exercise['type'],
                                'notes' => $exercise['notes'],
                                'target_sets' => $this->toNullableInt($exercise['target_sets']),
                                'target_reps_min' => $this->toNullableInt($exercise['target_reps_min']),
                                'target_reps_max' => $this->toNullableInt($exercise['target_reps_max']),
                                'target_weight' => $this->toNullableFloat($exercise['target_weight']),
                                'target_rpe' => $this->toNullableFloat($exercise['target_rpe']),
                                'target_tempo' => $exercise['target_tempo'],
                                'rest_after' => $this->toNullableInt($exercise['rest_after']),
                                'target_duration' => $this->toNullableInt($exercise['target_duration']),
                                'target_distance' => $this->toNullableFloat($exercise['target_distance']),
                                'target_pace_min' => $this->toNullableInt($exercise['target_pace_min']),
                                'target_pace_max' => $this->toNullableInt($exercise['target_pace_max']),
                                'target_heart_rate_zone' => $this->toNullableInt($exercise['target_heart_rate_zone']),
                                'target_heart_rate_min' => $this->toNullableInt($exercise['target_heart_rate_min']),
                                'target_heart_rate_max' => $this->toNullableInt($exercise['target_heart_rate_max']),
                                'target_power' => $this->toNullableInt($exercise['target_power']),
                            ])
                            ->all(),
                    ])
                    ->all(),
            ])
            ->map(fn (array $section): SectionData => SectionData::fromArray($section));
    }

    /**
     * @return array<string, mixed>
     */
    private function validationRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'required',
            'sections' => 'array',
            'sections.*.name' => 'required|string|max:255',
            'sections.*.notes' => 'nullable|string|max:5000',
            'sections.*.blocks' => 'array',
            'sections.*.blocks.*.block_type' => ['required', Rule::enum(BlockType::class)],
            'sections.*.blocks.*.rounds' => 'nullable|integer|min:1',
            'sections.*.blocks.*.rest_between_exercises' => 'nullable|integer|min:0',
            'sections.*.blocks.*.rest_between_rounds' => 'nullable|integer|min:0',
            'sections.*.blocks.*.time_cap' => 'nullable|integer|min:0',
            'sections.*.blocks.*.work_interval' => 'nullable|integer|min:0',
            'sections.*.blocks.*.rest_interval' => 'nullable|integer|min:0',
            'sections.*.blocks.*.notes' => 'nullable|string|max:5000',
            'sections.*.blocks.*.exercises' => 'array',
            'sections.*.blocks.*.exercises.*.name' => 'required|string|max:255',
            'sections.*.blocks.*.exercises.*.type' => 'required|in:strength,cardio,duration',
            'sections.*.blocks.*.exercises.*.notes' => 'nullable|string|max:5000',
            'sections.*.blocks.*.exercises.*.target_sets' => 'nullable|integer|min:1',
            'sections.*.blocks.*.exercises.*.target_reps_min' => 'nullable|integer|min:0',
            'sections.*.blocks.*.exercises.*.target_reps_max' => 'nullable|integer|min:0',
            'sections.*.blocks.*.exercises.*.target_weight' => 'nullable|numeric|min:0',
            'sections.*.blocks.*.exercises.*.target_rpe' => 'nullable|numeric|min:1|max:10',
            'sections.*.blocks.*.exercises.*.target_tempo' => 'nullable|string|max:20',
            'sections.*.blocks.*.exercises.*.rest_after' => 'nullable|integer|min:0',
            'sections.*.blocks.*.exercises.*.target_duration' => 'nullable|integer|min:0',
            'sections.*.blocks.*.exercises.*.target_distance' => 'nullable|numeric|min:0',
            'sections.*.blocks.*.exercises.*.target_pace_min' => 'nullable|integer|min:0',
            'sections.*.blocks.*.exercises.*.target_pace_max' => 'nullable|integer|min:0',
            'sections.*.blocks.*.exercises.*.target_heart_rate_zone' => 'nullable|integer|min:1|max:5',
            'sections.*.blocks.*.exercises.*.target_heart_rate_min' => 'nullable|integer|min:0',
            'sections.*.blocks.*.exercises.*.target_heart_rate_max' => 'nullable|integer|min:0',
            'sections.*.blocks.*.exercises.*.target_power' => 'nullable|integer|min:0',
        ];
    }
}
