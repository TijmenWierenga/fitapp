<?php

namespace Tests\Unit\Support;

use App\Enums\Workout\MuscleGroup;
use App\Support\Workout\MuscleLoadSummary;
use Tests\TestCase;

class MuscleLoadSummaryTest extends TestCase
{
    public function test_all_returns_the_full_loads_array(): void
    {
        $loads = [
            MuscleGroup::Quadriceps->value => [
                'total' => 50.0,
                'sources' => [['description' => 'Running', 'load' => 50.0]],
            ],
            MuscleGroup::Glutes->value => [
                'total' => 30.0,
                'sources' => [['description' => 'Running', 'load' => 30.0]],
            ],
        ];

        $summary = new MuscleLoadSummary($loads);

        $this->assertEquals($loads, $summary->all());
    }

    public function test_for_muscle_returns_data_for_specific_muscle_group(): void
    {
        $loads = [
            MuscleGroup::Quadriceps->value => [
                'total' => 50.0,
                'sources' => [['description' => 'Squats', 'load' => 50.0]],
            ],
        ];

        $summary = new MuscleLoadSummary($loads);

        $result = $summary->forMuscle(MuscleGroup::Quadriceps);

        $this->assertEquals(50.0, $result['total']);
        $this->assertCount(1, $result['sources']);
        $this->assertEquals('Squats', $result['sources'][0]['description']);
    }

    public function test_for_muscle_returns_default_for_missing_muscle(): void
    {
        $summary = new MuscleLoadSummary([]);

        $result = $summary->forMuscle(MuscleGroup::Chest);

        $this->assertEquals(0.0, $result['total']);
        $this->assertEmpty($result['sources']);
    }

    public function test_total_load_sums_all_muscle_totals(): void
    {
        $loads = [
            MuscleGroup::Quadriceps->value => [
                'total' => 50.0,
                'sources' => [],
            ],
            MuscleGroup::Glutes->value => [
                'total' => 30.0,
                'sources' => [],
            ],
            MuscleGroup::Hamstrings->value => [
                'total' => 20.0,
                'sources' => [],
            ],
        ];

        $summary = new MuscleLoadSummary($loads);

        $this->assertEquals(100.0, $summary->totalLoad());
    }

    public function test_total_load_returns_zero_for_empty_summary(): void
    {
        $summary = new MuscleLoadSummary([]);

        $this->assertEquals(0.0, $summary->totalLoad());
    }

    public function test_to_snapshot_data_converts_to_array_with_muscle_group_enums(): void
    {
        $loads = [
            MuscleGroup::Quadriceps->value => [
                'total' => 50.0,
                'sources' => [['description' => 'Running', 'load' => 50.0]],
            ],
            MuscleGroup::Calves->value => [
                'total' => 20.0,
                'sources' => [['description' => 'Running', 'load' => 20.0]],
            ],
        ];

        $summary = new MuscleLoadSummary($loads);
        $snapshotData = $summary->toSnapshotData();

        $this->assertCount(2, $snapshotData);

        $this->assertEquals(MuscleGroup::Quadriceps, $snapshotData[0]['muscle_group']);
        $this->assertEquals(50.0, $snapshotData[0]['total_load']);
        $this->assertEquals([['description' => 'Running', 'load' => 50.0]], $snapshotData[0]['source_breakdown']);

        $this->assertEquals(MuscleGroup::Calves, $snapshotData[1]['muscle_group']);
        $this->assertEquals(20.0, $snapshotData[1]['total_load']);
    }

    public function test_to_snapshot_data_returns_empty_array_for_empty_summary(): void
    {
        $summary = new MuscleLoadSummary([]);

        $this->assertEmpty($summary->toSnapshotData());
    }
}
