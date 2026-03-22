<?php

use App\Livewire\Dashboard\SessionLoadOverview;
use App\Models\Block;
use App\Models\BlockExercise;
use App\Models\CardioExercise;
use App\Models\Section;
use App\Models\User;
use App\Models\Workout;
use Carbon\CarbonImmutable;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->user = User::factory()->withTimezone('UTC')->create();
    $this->actingAs($this->user);
});

it('shows empty state when no data exists', function (): void {
    Livewire::test(SessionLoadOverview::class)
        ->assertSee('No session load data yet');
});

it('renders ACWR badge when EWMA data exists', function (): void {
    $now = CarbonImmutable::now();

    // Create several workouts over the past weeks to generate EWMA data
    for ($i = 1; $i <= 10; $i++) {
        $workout = Workout::factory()->create([
            'user_id' => $this->user->id,
            'completed_at' => $now->subDays($i * 3),
            'scheduled_at' => $now->subDays($i * 3)->subHour(),
            'rpe' => 7,
            'feeling' => 4,
        ]);

        $section = Section::factory()->create(['workout_id' => $workout->id]);
        $block = Block::factory()->distanceDuration()->create(['section_id' => $section->id]);
        $cardio = CardioExercise::factory()->create(['target_duration' => 2700]);
        BlockExercise::factory()->create([
            'block_id' => $block->id,
            'exerciseable_type' => $cardio->getMorphClass(),
            'exerciseable_id' => $cardio->id,
        ]);
    }

    Livewire::test(SessionLoadOverview::class)
        ->assertSee('ACWR')
        ->assertSee('Freshness')
        ->assertSee('Weekly sRPE')
        ->assertSee('Sessions')
        ->assertDontSee('No session load data yet');
});

it('populates EWMA chart data', function (): void {
    $now = CarbonImmutable::now();

    $workout = Workout::factory()->create([
        'user_id' => $this->user->id,
        'completed_at' => $now->subDays(2),
        'scheduled_at' => $now->subDays(2)->subHour(),
        'rpe' => 7,
        'feeling' => 4,
    ]);

    $section = Section::factory()->create(['workout_id' => $workout->id]);
    $block = Block::factory()->distanceDuration()->create(['section_id' => $section->id]);
    $cardio = CardioExercise::factory()->create(['target_duration' => 2700]);
    BlockExercise::factory()->create([
        'block_id' => $block->id,
        'exerciseable_type' => $cardio->getMorphClass(),
        'exerciseable_id' => $cardio->id,
    ]);

    // Verify chart renders with acute/chronic lines
    Livewire::test(SessionLoadOverview::class)
        ->assertSee('Acute (7-day)')
        ->assertSee('Chronic (28-day)');
});

it('shows ACWR zone warning for danger zone', function (): void {
    $now = CarbonImmutable::now();

    // Create only very recent heavy sessions (no history) to push ACWR high
    for ($i = 0; $i < 3; $i++) {
        $workout = Workout::factory()->create([
            'user_id' => $this->user->id,
            'completed_at' => $now->subDays($i),
            'scheduled_at' => $now->subDays($i)->subHour(),
            'rpe' => 10,
            'feeling' => 3,
        ]);

        $section = Section::factory()->create(['workout_id' => $workout->id]);
        $block = Block::factory()->distanceDuration()->create(['section_id' => $section->id]);
        $cardio = CardioExercise::factory()->create(['target_duration' => 7200]);
        BlockExercise::factory()->create([
            'block_id' => $block->id,
            'exerciseable_type' => $cardio->getMorphClass(),
            'exerciseable_id' => $cardio->id,
        ]);
    }

    // With only recent heavy sessions and no chronic history, ACWR should be danger zone
    Livewire::test(SessionLoadOverview::class)
        ->assertSee('High injury risk');
});
