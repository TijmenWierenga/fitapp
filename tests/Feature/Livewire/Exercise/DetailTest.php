<?php

use App\Livewire\Exercise\Detail;
use App\Models\Exercise;
use App\Models\MuscleGroup;
use App\Models\User;
use Livewire\Livewire;

it('loads exercise on event dispatch', function () {
    $user = User::factory()->create();
    $exercise = Exercise::factory()->create(['name' => 'Deadlift']);

    Livewire::actingAs($user)
        ->test(Detail::class)
        ->assertSet('showModal', false)
        ->assertSet('exercise', null)
        ->dispatch('show-exercise-detail', exerciseId: $exercise->id)
        ->assertSet('showModal', true)
        ->assertSee('Deadlift');
});

it('shows primary and secondary muscles', function () {
    $user = User::factory()->create();
    $exercise = Exercise::factory()->create();
    $primary = MuscleGroup::factory()->create(['label' => 'Hamstrings']);
    $secondary = MuscleGroup::factory()->create(['label' => 'Glutes']);

    $exercise->muscleGroups()->attach([
        $primary->id => ['load_factor' => 1.0],
        $secondary->id => ['load_factor' => 0.5],
    ]);

    Livewire::actingAs($user)
        ->test(Detail::class)
        ->dispatch('show-exercise-detail', exerciseId: $exercise->id)
        ->assertSee('Hamstrings')
        ->assertSee('Glutes');
});

it('shows instructions', function () {
    $user = User::factory()->create();
    $exercise = Exercise::factory()->create([
        'instructions' => ['Step one', 'Step two'],
    ]);

    Livewire::actingAs($user)
        ->test(Detail::class)
        ->dispatch('show-exercise-detail', exerciseId: $exercise->id)
        ->assertSee('Step one')
        ->assertSee('Step two');
});

it('closes the modal', function () {
    $user = User::factory()->create();
    $exercise = Exercise::factory()->create();

    Livewire::actingAs($user)
        ->test(Detail::class)
        ->dispatch('show-exercise-detail', exerciseId: $exercise->id)
        ->assertSet('showModal', true)
        ->call('closeModal')
        ->assertSet('showModal', false)
        ->assertSet('exercise', null);
});

it('shows exercise properties as badges', function () {
    $user = User::factory()->create();
    $exercise = Exercise::factory()->create([
        'category' => 'strength',
        'level' => 'expert',
        'equipment' => 'barbell',
    ]);

    Livewire::actingAs($user)
        ->test(Detail::class)
        ->dispatch('show-exercise-detail', exerciseId: $exercise->id)
        ->assertSee('Strength')
        ->assertSee('Expert')
        ->assertSee('Barbell');
});
