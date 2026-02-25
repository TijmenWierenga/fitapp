<?php

use App\Livewire\Dashboard\NextWorkout;
use App\Livewire\Workout\Show;
use App\Models\User;
use App\Models\Workout;
use Livewire\Livewire;

it('can create a workout with notes', function () {
    $user = User::factory()->create();

    $workout = Workout::factory()->for($user)->create([
        'notes' => 'Focus on maintaining cadence throughout the run.',
    ]);

    expect($workout->notes)->toBe('Focus on maintaining cadence throughout the run.');
});

it('can create a workout without notes', function () {
    $user = User::factory()->create();

    $workout = Workout::factory()->for($user)->create([
        'notes' => null,
    ]);

    expect($workout->notes)->toBeNull();
});

it('can update workout notes', function () {
    $workout = Workout::factory()->create([
        'notes' => 'Original notes',
        'completed_at' => null,
    ]);

    $workout->update(['notes' => 'Updated notes']);

    expect($workout->fresh()->notes)->toBe('Updated notes');
});

it('can remove workout notes by setting to null', function () {
    $workout = Workout::factory()->create([
        'notes' => 'Some notes',
        'completed_at' => null,
    ]);

    $workout->update(['notes' => null]);

    expect($workout->fresh()->notes)->toBeNull();
});

it('can remove workout notes by setting to empty string', function () {
    $workout = Workout::factory()->create([
        'notes' => 'Some notes',
        'completed_at' => null,
    ]);

    $workout->update(['notes' => '']);

    expect($workout->fresh()->notes)->toBeNull();
});

it('trims whitespace from notes', function () {
    $workout = Workout::factory()->create([
        'notes' => '  Notes with leading and trailing spaces  ',
    ]);

    expect($workout->notes)->toBe('Notes with leading and trailing spaces');
});

it('converts whitespace-only notes to null', function () {
    $workout = Workout::factory()->create([
        'notes' => '   ',
    ]);

    expect($workout->notes)->toBeNull();
});

it('duplicates workout with notes', function () {
    $workout = Workout::factory()->create([
        'notes' => 'Important workout notes',
    ]);

    $duplicatedWorkout = $workout->duplicate(now()->addDay());

    expect($duplicatedWorkout->notes)->toBe('Important workout notes');
});

it('duplicates workout without notes', function () {
    $workout = Workout::factory()->create([
        'notes' => null,
    ]);

    $duplicatedWorkout = $workout->duplicate(now()->addDay());

    expect($duplicatedWorkout->notes)->toBeNull();
});

it('stores notes with maximum length', function () {
    $longNotes = str_repeat('a', 65535);

    $workout = Workout::factory()->create([
        'notes' => $longNotes,
    ]);

    expect($workout->notes)->toBe($longNotes);
});

it('renders markdown bold text in workout show page', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'notes' => 'This is **bold** text',
        'scheduled_at' => now()->addDay(),
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->assertSeeHtml('<strong>bold</strong>');
});

it('renders markdown italic text in workout show page', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'notes' => 'This is *italic* text',
        'scheduled_at' => now()->addDay(),
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->assertSeeHtml('<em>italic</em>');
});

it('renders markdown list in workout show page', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'notes' => "- Item 1\n- Item 2",
        'scheduled_at' => now()->addDay(),
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->assertSeeHtml('<li>Item 1</li>');
});

it('escapes raw HTML in notes to prevent XSS', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'notes' => '<script>alert("xss")</script>',
        'scheduled_at' => now()->addDay(),
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['workout' => $workout])
        ->assertDontSeeHtml('<script>alert("xss")</script>')
        ->assertSeeHtml('&lt;script&gt;');
});

it('shows workout in schedule card when it has notes', function () {
    $user = User::factory()->create();
    $workout = Workout::factory()->for($user)->create([
        'notes' => 'Remember to **warm up** properly',
        'scheduled_at' => now()->addDay(),
        'completed_at' => null,
    ]);

    Livewire::actingAs($user)
        ->test(NextWorkout::class)
        ->assertSee($workout->name);
});
