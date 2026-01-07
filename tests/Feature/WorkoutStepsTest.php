<?php

namespace Tests\Feature;

use App\Enums\WorkoutType;
use App\Livewire\Workout\Create;
use App\Models\User;
use App\Models\Workout;
use Livewire\Livewire;

it('can create a workout with steps', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Create::class)
        ->set('name', 'Interval Run')
        ->set('type', 'running')
        ->set('scheduled_date', '2026-01-10')
        ->set('scheduled_time', '08:00')
        ->call('addStep')
        ->set('steps.0.intensity', 'warmup')
        ->set('steps.0.duration_value', '600')
        ->call('addRepeat')
        ->set('steps.1.duration_value', '5')
        ->set('steps.1.children.0.intensity', 'active')
        ->set('steps.1.children.0.duration_value', '60')
        ->set('steps.1.children.1.intensity', 'rest')
        ->set('steps.1.children.1.duration_value', '60')
        ->call('save')
        ->assertRedirect('/dashboard');

    $workout = Workout::where('name', 'Interval Run')->first();
    expect($workout)->not->toBeNull()
        ->and($workout->type)->toBe(WorkoutType::Running)
        ->and($workout->steps)->toHaveCount(2);

    $warmup = $workout->steps->first();
    expect($warmup->intensity->value)->toBe('warmup')
        ->and($warmup->duration_value)->toBe('600');

    $repeat = $workout->steps->last();
    expect($repeat->type->value)->toBe('repetition')
        ->and($repeat->duration_value)->toBe('5')
        ->and($repeat->children)->toHaveCount(2);

    expect($repeat->children->first()->intensity->value)->toBe('active')
        ->and($repeat->children->last()->intensity->value)->toBe('rest');
});

it('can remove steps', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Create::class)
        ->call('addStep')
        ->call('addStep')
        ->assertCount('steps', 2)
        ->call('removeStep', 0)
        ->assertCount('steps', 1);
});

it('can add and remove steps in a repeat block', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Create::class)
        ->call('addRepeat')
        ->assertCount('steps.0.children', 2)
        ->call('addChildStep', 0)
        ->assertCount('steps.0.children', 3)
        ->call('removeChildStep', 0, 0)
        ->assertCount('steps.0.children', 2);
});
