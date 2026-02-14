<?php

use App\Livewire\Exercise\Show;
use App\Models\Exercise;
use App\Models\MuscleGroup;
use App\Models\User;
use Livewire\Livewire;

it('renders the exercise detail page', function () {
    $user = User::factory()->create();
    $exercise = Exercise::factory()->create([
        'name' => 'Bench Press',
        'category' => 'strength',
        'level' => 'intermediate',
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['exercise' => $exercise])
        ->assertSee('Bench Press')
        ->assertSee('strength')
        ->assertSee('intermediate');
});

it('shows primary and secondary muscles', function () {
    $user = User::factory()->create();
    $exercise = Exercise::factory()->create();
    $primary = MuscleGroup::factory()->create(['label' => 'Chest']);
    $secondary = MuscleGroup::factory()->create(['label' => 'Triceps']);

    $exercise->muscleGroups()->attach([
        $primary->id => ['load_factor' => 1.0],
        $secondary->id => ['load_factor' => 0.5],
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['exercise' => $exercise])
        ->assertSee('Chest')
        ->assertSee('Triceps')
        ->assertSee('Primary')
        ->assertSee('Secondary');
});

it('shows instructions', function () {
    $user = User::factory()->create();
    $exercise = Exercise::factory()->create([
        'instructions' => ['Grip the bar', 'Lower to chest', 'Press up'],
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['exercise' => $exercise])
        ->assertSee('Instructions')
        ->assertSee('Grip the bar')
        ->assertSee('Lower to chest')
        ->assertSee('Press up');
});

it('shows aliases', function () {
    $user = User::factory()->create();
    $exercise = Exercise::factory()->create([
        'aliases' => ['Flat Bench', 'Barbell Press'],
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['exercise' => $exercise])
        ->assertSee('Flat Bench')
        ->assertSee('Barbell Press');
});

it('shows description', function () {
    $user = User::factory()->create();
    $exercise = Exercise::factory()->create([
        'description' => 'A compound upper body exercise.',
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['exercise' => $exercise])
        ->assertSee('A compound upper body exercise.');
});

it('shows tips', function () {
    $user = User::factory()->create();
    $exercise = Exercise::factory()->create([
        'tips' => ['Keep your back flat', 'Breathe out on press'],
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['exercise' => $exercise])
        ->assertSee('Tips')
        ->assertSee('Keep your back flat')
        ->assertSee('Breathe out on press');
});

it('resolves exercise by slug', function () {
    $user = User::factory()->create();
    $exercise = Exercise::factory()->create([
        'name' => 'Barbell Squat',
        'slug' => 'barbell-squat',
    ]);

    $response = $this->actingAs($user)->get('/exercises/barbell-squat');

    $response->assertSuccessful();
    $response->assertSee('Barbell Squat');
});

it('returns 404 for a missing slug', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/exercises/nonexistent-exercise');

    $response->assertNotFound();
});

it('requires authentication', function () {
    $exercise = Exercise::factory()->create();

    $response = $this->get("/exercises/{$exercise->slug}");

    $response->assertRedirect('/login');
});

it('shows exercise properties in sidebar', function () {
    $user = User::factory()->create();
    $exercise = Exercise::factory()->create([
        'force' => 'push',
        'mechanic' => 'compound',
        'equipment' => 'barbell',
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['exercise' => $exercise])
        ->assertSee('push')
        ->assertSee('compound')
        ->assertSee('barbell');
});
