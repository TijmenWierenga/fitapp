<?php

use App\Livewire\Dashboard\NextWorkout;
use App\Models\Step;
use App\Models\User;
use App\Models\Workout;
use Livewire\Livewire;

it('displays the steps of the next workout', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create(['scheduled_at' => now()->addDay()]);

    $step1 = Step::factory()->for($workout)->create(['name' => 'Warm up', 'sort_order' => 1]);
    $step2 = Step::factory()->for($workout)->create(['name' => 'Main set', 'sort_order' => 2]);
    $step3 = Step::factory()->for($workout)->create(['name' => 'Cool down', 'sort_order' => 3]);

    Livewire::actingAs($user)
        ->test(NextWorkout::class)
        ->assertSee('Warm up')
        ->assertSee('Main set')
        ->assertSee('Cool down')
        ->assertSeeHtml('<table')
        ->assertSeeHtml('<th')
        ->assertSeeHtml('<td');
});

it('displays repeat blocks in the next workout overview', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create(['scheduled_at' => now()->addDay()]);

    $repeat = Step::factory()->for($workout)->create([
        'step_kind' => \App\Enums\Workout\StepKind::Repeat,
        'repeat_count' => 5,
        'sort_order' => 1,
    ]);

    $childStep = Step::factory()->for($workout)->create([
        'parent_step_id' => $repeat->id,
        'name' => 'Fast Run',
        'sort_order' => 1,
    ]);

    Livewire::actingAs($user)
        ->test(NextWorkout::class)
        ->assertSee('Repeat 5x')
        ->assertSee('Fast Run');
});

it('limits the number of displayed steps and shows a link to view all steps', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create(['scheduled_at' => now()->addDay()]);

    Step::factory()->for($workout)->count(7)->create();

    Livewire::actingAs($user)
        ->test(NextWorkout::class)
        ->assertSee('View all 7 steps');
});

it('displays total duration and targets in the next workout overview', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create(['scheduled_at' => now()->addDay()]);

    Step::factory()->for($workout)->create([
        'name' => 'Main Set',
        'duration_type' => \App\Enums\Workout\DurationType::Time,
        'duration_value' => 3600, // 1 hour
        'target_type' => \App\Enums\Workout\TargetType::HeartRate,
        'target_mode' => \App\Enums\Workout\TargetMode::Range,
        'target_low' => 140,
        'target_high' => 150,
        'sort_order' => 1,
    ]);

    Livewire::actingAs($user)
        ->test(NextWorkout::class)
        ->assertSee('Est. 1h')
        ->assertSee('140–150 BPM');
});

it('displays estimated totals for mixed workout steps', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create(['scheduled_at' => now()->addDay()]);

    // 15 min Warmup (900s) -> Est. 2143m @ 7:00 min/km
    Step::factory()->for($workout)->create([
        'duration_type' => \App\Enums\Workout\DurationType::Time,
        'duration_value' => 900,
        'intensity' => \App\Enums\Workout\Intensity::Warmup,
        'sort_order' => 1,
    ]);

    // 7 km Run (7000m) -> Est. 2100s @ 5:00 min/km
    Step::factory()->for($workout)->create([
        'duration_type' => \App\Enums\Workout\DurationType::Distance,
        'duration_value' => 7000,
        'intensity' => \App\Enums\Workout\Intensity::Active,
        'sort_order' => 2,
    ]);

    // Total Duration: 900 + 2100 = 3000s = 50 min
    // Total Distance: 2143 + 7000 = 9143m = 9.14 km (approx, DistanceConverter::format might round)

    Livewire::actingAs($user)
        ->test(NextWorkout::class)
        ->assertSee('Est. 50min')
        ->assertSee('Est. 9.143 km');
});
