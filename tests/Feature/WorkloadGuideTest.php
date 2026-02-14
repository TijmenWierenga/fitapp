<?php

use App\Models\User;

use function Pest\Laravel\get;

it('can view the workload guide as an authenticated user', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    get(route('workload-guide'))
        ->assertOk()
        ->assertSee('Workload Guide')
        ->assertSee('What is workload tracking?');
});

it('redirects unauthenticated users to login', function (): void {
    get(route('workload-guide'))
        ->assertRedirect(route('login'));
});
