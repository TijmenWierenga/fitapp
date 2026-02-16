<?php

use App\Models\User;

use function Pest\Laravel\get;

it('can view the docs index as an authenticated user', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    get(route('docs.index'))
        ->assertOk()
        ->assertSee('Documentation');
});

it('redirects unauthenticated users to login from docs index', function (): void {
    get(route('docs.index'))
        ->assertRedirect(route('login'));
});

it('can view the workload guide as an authenticated user', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    get(route('docs.workload-guide'))
        ->assertOk()
        ->assertSee('Workload Guide')
        ->assertSee('What is workload tracking?');
});

it('redirects unauthenticated users to login from workload guide', function (): void {
    get(route('docs.workload-guide'))
        ->assertRedirect(route('login'));
});

it('can view the garmin export guide as an authenticated user', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    get(route('docs.garmin-export'))
        ->assertOk()
        ->assertSee('Garmin Export Guide')
        ->assertSee('What is a FIT file?');
});

it('redirects unauthenticated users to login from garmin export guide', function (): void {
    get(route('docs.garmin-export'))
        ->assertRedirect(route('login'));
});

it('redirects old workload guide URL to new docs location', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    get('/workload-guide')
        ->assertRedirect('/docs/workload-guide');
});
