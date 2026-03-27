<?php

declare(strict_types=1);

use App\Livewire\Workouts\ImportGarmin;
use App\Models\User;
use Livewire\Livewire;

it('renders the import page for authenticated users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('workouts.import'))
        ->assertOk();
});

it('redirects unauthenticated users', function () {
    $this->get(route('workouts.import'))
        ->assertRedirect();
});

it('shows the upload step initially', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ImportGarmin::class)
        ->assertSet('step', 'upload')
        ->assertSee('Upload .FIT file');
});
